<?php 

class Azure extends API {

    function getInfo() : array
    {
        return [
            "icon" => 'https://img-prod-cms-rt-microsoft-com.akamaized.net/cms/api/am/imageFileData/RE1Mu3b?ver=5c31',
            "site" => 'https://azure.microsoft.com/fr-fr/services/cognitive-services/computer-vision/',
            "info" => `
            An AI service powered by Microsoft  that analyzes content in images and video
            `,
        ];
    }

    function getConfParams() : array
    {
        return [
            'ENDPOINT' => 'API Endpoint', 
            'KEY'=> 'API Key'
        ];
    }

    function generateTags($conf, $params) : array
    {
        $file_path = $this->getFileName($params['imageId']);

        $filesize = filesize($file_path);
        $fp = fopen($file_path, 'rb');
        $data = fread($fp, $filesize);
        fclose($fp);

        $ch = curl_init();
        
        $url = $conf["ENDPOINT"]."/vision/v3.2/analyze?visualFeatures=tags&language=".$params['language'];
        
        
        $headers = array(
            'Content-Type: application/octet-stream',
            'Ocp-Apim-Subscription-Key: '.$conf["KEY"],
        );
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        if (curl_errno($ch)) 
        {
            return [curl_error($ch)];
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $json_response = json_decode($response);
        
        if (!property_exists($json_response, "tags"))
            throw new Exception('Api Error');

        $tags = [];

        for ($i=0; $i < min([count($json_response->tags), $params['limit']]); $i++) { 
            $tagObjectArray = json_decode(json_encode($json_response->tags[$i]), true);
            array_push($tags, $tagObjectArray["name"]);
        }
        
        return $tags;
    }
}