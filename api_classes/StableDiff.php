<?php 

class StableDiff extends API {

    function getInfo() : array
    {
        return [
            "icon" => 'https://imagga.com/static/images/logo.svg',
            "site" => 'https://example.com',
            "info" => `
            Stable Diffusion Interrogation
            `
        ];
    }

    function getConfParams() : array
    {
        return [
            'USER' => 'API Key', 
            'USER_PASSWORD'=> 'API Secret'
        ];
    }

    function generateTags($conf, $params) : array
    {
        $file_path = $this->getFileName($params['imageId']);

        if (!(isset($conf['USER']) && isset($conf['USER_PASSWORD'])))
            throw new Exception('API parameters are not set');

        $api_credentials = array(
            'key' => $conf['USER'],
            'secret' => $conf['USER_PASSWORD']
        );

        $type = pathinfo($file_path, PATHINFO_EXTENSION);
        $data = file_get_contents($file_path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, "http://localhost:7860/sdapi/v1/interrogate");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'))
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        // curl_setopt($ch, CURLOPT_USERPWD, $api_credentials['key'].':'.$api_credentials['secret']);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        $data = [
            'image' => $base64,
            'model' => 'deepdanbooru',
        ];
        $json_data = json_encode($data)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        
        
        if (curl_errno($ch)) 
        {
            return [curl_error($ch)];
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $json_response = json_decode($response);

        if (!property_exists($json_response, "result") || !property_exists($json_response->result, "caption"))
            throw new Exception('Api Error');

        $tags = [];

        foreach ($json_response->result->tags as $tagObject) 
        {
            $tagObjectArray = json_decode(json_encode($tagObject), true);
            array_push($tags, $tagObjectArray["caption"]);
        }
        
        return $tags;
    }
}