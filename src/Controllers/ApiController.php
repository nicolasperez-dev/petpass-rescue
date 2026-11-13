<?php
declare(strict_types=1);

final class ApiController
{
    public function bot(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $payload=json_decode(file_get_contents('php://input'),true) ?: [];
        $question=mb_strtolower(trim((string)($payload['question']??'')));
        $locale=I18n::locale();
        if($question===''){ echo json_encode(['answer'=>'Escreva uma pergunta para o assistente.'],JSON_UNESCAPED_UNICODE); return; }
        $faqs=Database::fetchAll('SELECT * FROM bot_faqs WHERE active=1 AND language=? ORDER BY sort_order',[$locale]);
        $best=null; $score=0;
        foreach($faqs as $faq){
            $current=0;
            foreach(preg_split('/\s+/',mb_strtolower($faq['keywords'])) as $keyword){ if($keyword!=='' && str_contains($question,$keyword)) $current++; }
            if($current>$score){$score=$current;$best=$faq;}
        }
        $answer=$best?$best['answer']:($locale==='es'?'Todavía no tengo una respuesta exacta. Probá preguntar por registro, QR/NFC, modo perdido o planes.':'Ainda não tenho uma resposta exata. Tente perguntar sobre cadastro, QR/NFC, modo perdido ou planos.');
        echo json_encode(['answer'=>$answer],JSON_UNESCAPED_UNICODE);
    }

    public function breeds(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $speciesId=(int)($_GET['species_id']??0);
        echo json_encode(Database::fetchAll('SELECT id,name FROM breeds WHERE species_id=? AND active=1 ORDER BY name',[$speciesId]),JSON_UNESCAPED_UNICODE);
    }
}
