<?php

namespace App\Helpers;

class OpenAI {

    private $openAI_url;
    private $openAI_key;
    private $params = [];


    public function __construct()
    {
        $this->openAI_key = config('openai.token');
        $this->openAI_url = config('openai.url');
        $this->params = [
            'max_tokens' => config('openai.max_tokens'),
            'temperature' => config('openai.temperature'),
            'top_p' => config('openai.top_p'),
        ];
    }

    public function askChatGPT($prompt, $params = []){
        if(!config('openai.enabled')){ return false; }
        $this->params = array_merge($this->params, $params);

        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $this->params['messages'] = $messages;
        $data = json_encode($this->params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->openAI_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $this->openAI_key,
        ]);
        
        $response = curl_exec($ch);
        if($response === false){
            // print("Error: ". curl_error($ch) . "\n");
            return false;
        }
        $result = json_decode($response);
        if(isset($result->choices[0]->message->content)){
            return json_decode($result->choices[0]->message->content);
        }

        curl_close($ch);
        return false;
    }

}