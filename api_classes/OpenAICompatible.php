<?php

class OpenAICompatible extends API {

    function getInfo() : array
    {
        return [
            "icon" => '',
            "site" => 'https://platform.openai.com/docs/api-reference/chat',
            "info" => 'Self-hosted OpenAI-compatible vision API. Works with any server that implements the /v1/chat/completions endpoint, including llama.cpp, Ollama, vLLM, LiteLLM, and OpenAI itself. Requires a vision-capable model.',
        ];
    }

    function getConfParams() : array
    {
        return [
            'ENDPOINT'          => 'API Base URL',
            'API_KEY'           => 'API Key (optional)',
            'MODEL'             => 'Model Name',
            'MAX_TOKENS'        => 'Max Tokens',
            'PROMPT'            => 'Custom Prompt (optional)',
            'WRITE_DESCRIPTION' => 'Write description as photo comment',
        ];
    }

    function getConfFieldTypes() : array
    {
        return [
            'PROMPT'            => 'textarea',
            'WRITE_DESCRIPTION' => 'checkbox',
        ];
    }

    function generateTags($conf, $params) : array
    {
        $file_path = $this->getFileName($params['imageId']);

        if (empty($conf['ENDPOINT']) || empty($conf['MODEL']))
            throw new Exception('API parameters are not set');

        // getFileName() returns a derivative URL or path. Try to resolve it to a
        // filesystem path so file_get_contents() works without an HTTP round-trip.
        $fs_path = $file_path;
        if (defined('PHPWG_ROOT_PATH') && !preg_match('/^https?:\/\//', $file_path)) {
            $candidate = realpath(PHPWG_ROOT_PATH . $file_path);
            if ($candidate !== false && file_exists($candidate)) {
                $fs_path = $candidate;
            }
        }

        $pathinfo = pathinfo($fs_path);
        $mime_types = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
        ];
        $ext = strtolower($pathinfo['extension'] ?? 'jpg');
        $mime_content_type = $mime_types[$ext] ?? 'image/jpeg';

        $data = @file_get_contents($fs_path);
        if ($data === false)
            throw new Exception('Cannot read image file: ' . $fs_path);

        $base64  = base64_encode($data);
        $dataUri = 'data:' . $mime_content_type . ';base64,' . $base64;

        $limit  = (int)($params['limit'] ?? 20);
        $prompt = !empty($conf['PROMPT'])
            ? $conf['PROMPT']
            : 'Analyze this image and respond with a JSON object containing two keys: "description" (a 2-3 sentence description of the image) and "tags" (an array of up to ' . $limit . ' relevant keyword tags). Respond with only the JSON object, no markdown or extra text.';

        $max_tokens = !empty($conf['MAX_TOKENS']) ? (int)$conf['MAX_TOKENS'] : 300;

        $payload = [
            'model'      => $conf['MODEL'],
            'max_tokens' => $max_tokens,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type'      => 'image_url',
                            'image_url' => ['url' => $dataUri],
                        ],
                    ],
                ],
            ],
        ];

        $endpoint = rtrim($conf['ENDPOINT'], '/') . '/v1/chat/completions';

        $api_key = !empty($conf['API_KEY']) ? $conf['API_KEY'] : 'none';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        // 120s is enough for most local models; increase for large models on slow hardware
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Connection error: ' . $error);
        }
        curl_close($ch);

        $json_response = json_decode($response);

        if (!isset($json_response->choices[0]->message->content))
            throw new Exception('API Error: ' . $response);

        $content = $json_response->choices[0]->message->content;

        // Strip markdown code fences that some models add
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```\s*$/m', '', $content);
        $content = trim($content);

        $parsed = json_decode($content, true);

        $tags        = [];
        $description = '';

        if (is_array($parsed)) {
            if (!empty($parsed['tags']) && is_array($parsed['tags'])) {
                $tags = array_slice(array_values($parsed['tags']), 0, $limit);
            }
            if (!empty($parsed['description']) && is_string($parsed['description'])) {
                $description = $parsed['description'];
            }
        } else {
            // Fallback for models that return free text instead of JSON.
            // Use the raw content as the description and try to split it into tags.
            $description = $content;

            $candidates = preg_split('/[\n,]+/', $content);
            foreach ($candidates as $candidate) {
                $candidate = trim($candidate);
                // Skip empty strings, sentences (> 5 words), and overly long entries
                if ($candidate === '') continue;
                if (str_word_count($candidate) > 5) continue;
                if (strlen($candidate) > 50) continue;
                $tags[] = $candidate;
                if (count($tags) >= $limit) break;
            }
        }

        if (!empty($conf['WRITE_DESCRIPTION']) && $conf['WRITE_DESCRIPTION'] === '1' && !empty($description)) {
            $query = '
UPDATE ' . IMAGES_TABLE . '
  SET comment = \'' . pwg_db_real_escape_string($description) . '\'
  WHERE id = ' . ((int)$params['imageId']) . '
;';
            pwg_query($query);
        }

        return $tags;
    }
}
