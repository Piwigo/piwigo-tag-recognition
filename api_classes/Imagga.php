<?php 

class Imagga extends API {

    function getInfo() : array
    {
        return [
            "icon" => 'https://imagga.com/static/images/logo.svg',
            "site" => 'https://imagga.com/',
            "info" => `
            Imagga is a computer vision artificial intelligence company. Imagga Image Recognition API features auto-tagging, auto-categorization, face recognition, visual search, content moderation, auto-cropping, color extraction, custom training and ready-to-use models. Available in the Cloud and on On-Premise. It is currently deployed in leading digital asset management solutions and personal cloud platforms and consumer facing apps.
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
        
        curl_setopt($ch, CURLOPT_URL, "https://api.imagga.com/v2/tags");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERPWD, $api_credentials['key'].':'.$api_credentials['secret']);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        $fields = [
            'image_base64' => $base64,
            'language' => $params['language'],
            'limit' => $params['limit'],
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        
        
        if (curl_errno($ch)) 
        {
            return [curl_error($ch)];
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $json_response = json_decode($response);

        if (!property_exists($json_response, "result") || !property_exists($json_response->result, "tags"))
            throw new Exception('Api Error');

        $tags = [];

        foreach ($json_response->result->tags as $tagObject) 
        {
            $tagObjectArray = json_decode(json_encode($tagObject), true);
            array_push($tags, $tagObjectArray["tag"][$params['language']]);
        }
        
        return $tags;
    }
}