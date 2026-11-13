<?php
declare(strict_types=1);

final class Security
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function validateCsrf(): void
    {
        $provided = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!hash_equals(self::csrfToken(), $provided)) {
            http_response_code(419);
            exit('Sessão expirada. Volte à página anterior e tente novamente.');
        }
    }

    public static function randomCode(int $bytes = 12): string
    {
        return strtoupper(rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', 'AB'), '='));
    }

    public static function petPublicId(): string
    {
        return 'PET-' . substr(self::randomCode(8), 0, 10);
    }

    public static function normalizePhone(?string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', (string) $phone) ?? '';
    }

    public static function ipHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return hash_hmac('sha256', $ip, (string) config('app.session_name'));
    }

    public static function upload(array $file, string $category, array $allowedMime, int $maxBytes): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'O arquivo é maior que o limite configurado no PHP do servidor.',
                UPLOAD_ERR_FORM_SIZE => 'O arquivo é maior que o limite permitido por este formulário.',
                UPLOAD_ERR_PARTIAL => 'O envio foi interrompido antes de terminar. Tente novamente.',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi selecionado.',
                UPLOAD_ERR_NO_TMP_DIR => 'O servidor não possui pasta temporária para uploads.',
                UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o arquivo no disco.',
                UPLOAD_ERR_EXTENSION => 'Uma extensão do servidor bloqueou o upload.',
            ];
            throw new RuntimeException($messages[$error] ?? 'O arquivo não foi recebido corretamente. Código: ' . $error);
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('O arquivo temporário não é válido. Recarregue a página e tente novamente.');
        }

        $size = (int) ($file['size'] ?? 0);
        $serverLimit = self::serverUploadLimitBytes();
        $effectiveLimit = $serverLimit > 0 ? min($maxBytes, $serverLimit) : $maxBytes;
        if ($size <= 0) throw new RuntimeException('O arquivo está vazio.');
        if ($size > $effectiveLimit) {
            throw new RuntimeException('O arquivo ultrapassa o limite de ' . self::humanBytes($effectiveLimit) . '.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string) $finfo->file($tmp));
        $aliases = [
            'image/jpg' => 'image/jpeg',
            'image/pjpeg' => 'image/jpeg',
            'audio/x-m4a' => 'audio/mp4',
            'audio/m4a' => 'audio/mp4',
            'audio/x-wav' => 'audio/wav',
            'application/x-pdf' => 'application/pdf',
        ];
        $mime = $aliases[$mime] ?? $mime;
        $allowedMime = array_map(static fn(string $m): string => $aliases[strtolower($m)] ?? strtolower($m), $allowedMime);
        if (!in_array($mime, $allowedMime, true)) {
            throw new RuntimeException('Formato não permitido (' . ($mime ?: 'desconhecido') . '). Use JPG, PNG, WEBP, MP3, M4A, WEBM, OGG, WAV, MP4 ou PDF conforme a seção.');
        }

        $extensions = [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
            'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/webm' => 'webm',
            'audio/ogg' => 'ogg', 'audio/wav' => 'wav',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4', 'video/webm' => 'webm',
        ];
        $extension = $extensions[$mime] ?? 'bin';

        $safeCategory = trim(preg_replace('#[^a-zA-Z0-9/_-]#', '', $category) ?: 'files', '/');
        $directory = PUBLIC_PATH . '/uploads/' . $safeCategory;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Não foi possível preparar a pasta de upload. Verifique permissões 755/775 em uploads/.');
        }
        if (!is_writable($directory)) {
            throw new RuntimeException('A pasta uploads/' . $safeCategory . ' não possui permissão de escrita no servidor.');
        }

        $filename = date('Ymd') . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $directory . '/' . $filename;
        if (!move_uploaded_file($tmp, $destination)) {
            throw new RuntimeException('Não foi possível salvar o arquivo. Verifique espaço e permissões no Ferozo.');
        }
        @chmod($destination, 0644);

        return [
            'path' => 'uploads/' . $safeCategory . '/' . $filename,
            'mime' => $mime,
            'size' => $size,
            'original' => mb_substr(basename((string) ($file['name'] ?? 'arquivo')), 0, 180),
        ];
    }

    public static function serverUploadLimitBytes(): int
    {
        $upload = self::iniBytes((string) ini_get('upload_max_filesize'));
        $post = self::iniBytes((string) ini_get('post_max_size'));
        if ($upload <= 0) return $post;
        if ($post <= 0) return $upload;
        return min($upload, $post);
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') return 0;
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    public static function humanBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        return $bytes . ' bytes';
    }
}
