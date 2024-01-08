<?php 

class MyKeyworder extends API {

    function getInfo() : array
    {
        return [
            "icon" => 'https://mykeyworder.com/img/logo.png',
            "site" => 'https://mykeyworder.com/',
            "info" => `
            MyKeyworder is a keywording tool for photographers. The image recognition API provides programmatic access to the MyKeyworder Image Recognition service for automatically keywording larger volumes of images.
            `
        ];
    }

    function getConfParams() : array
    {
        return [
            'USER' => 'API Username', 
            'USER_PASSWORD'=> 'API Key'
        ];
    }

    function generateTags($conf, $params) : array
    {

        if (isset($_SERVER['HTTPS'])){
            $file_path = "https://".$_SERVER['HTTP_HOST'].ltrim($this->getFileName($params['imageId']), '.');
        } else {
            $file_path = "http://".$_SERVER['HTTP_HOST'].ltrim($this->getFileName($params['imageId']), '.');
        }

        if (!(isset($conf['USER']) && isset($conf['USER_PASSWORD'])))
            throw new Exception('API parameters are not set');

        $api_credentials = array(
            'key' => $conf['USER'],
            'secret' => $conf['USER_PASSWORD']
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://mykeyworder.com/api/v1/analyze?url='.urlencode($file_path));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_USERPWD, $api_credentials['key'].':'.$api_credentials['secret']);

        $response = curl_exec($ch);
        curl_close($ch);
        
        $json_response = json_decode($response);

        $tags = [];
        
        if(isset($json_response->keywords)){
            foreach ($json_response->keywords as $tagObject) 
            {
                $tagObjectArray = json_decode(json_encode($tagObject), true);
                array_push($tags, $tagObjectArray);
            }
        }
        
        return $tags;
    }
}
