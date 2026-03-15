# AutoTag

Piwigo plugin to suggest tags on image (using external API). Internal Name : `tag_recognition`.

Generate tags, choose them and apply them on the image page.

Generate and automatically apply tags to a bunch of image on the batch manager. 

## Available APIs
- [Imagga](https://imagga.com)
- [Microsoft Azure Computer Vision](https://azure.microsoft.com/fr-fr/services/cognitive-services/computer-vision/) (you'll have to create an application on Azure)
- **New** OpenAI-compatible self-hosted endpoint (llama.cpp, Ollama, vLLM, LiteLLM, OpenAI, etc.)

## Usage
* Install the plugin on your Piwigo
* Create an Imagga Account on https://imagga.com/auth/signup (or [here](https://azure.microsoft.com/fr-fr/free/cognitive-services/) for Microsoft Azure), or set up a self-hosted vision model (see below)
* Enter your API credentials on the plugin configuration page
* Go to an image modification page and click on the little robot next to the tags input
* Generate tags, select the ones you want and apply them

### Self-hosted / OpenAI-compatible backend

The **OpenAI-compatible** provider works with any server that implements the `/v1/chat/completions` endpoint with vision support. Common options:

| Server | Example base URL |
|--------|-----------------|
| [Ollama](https://ollama.com) | `http://localhost:11434` |
| [llama.cpp](https://github.com/ggerganov/llama.cpp) | `http://localhost:8080` |
| [vLLM](https://github.com/vllm-project/vllm) | `http://localhost:8000` |
| [LiteLLM](https://github.com/BerriAI/litellm) | `http://localhost:4000` |
| OpenAI | `https://api.openai.com` |

**Configuration fields:**

| Field | Description |
|-------|-------------|
| API Base URL | Base URL of the server (the plugin appends `/v1/chat/completions`) |
| API Key | Bearer token — leave empty or set to `none` for local servers that don't require auth |
| Model Name | The vision-capable model to use (e.g. `llava`, `gpt-4o`, `llava-llama3`) |
| Max Tokens | Maximum tokens in the model's response (default 300) |
| Custom Prompt | Override the default JSON prompt. Leave empty to use the built-in prompt. |
| Write description as photo comment | When checked, the model's image description is also saved to the photo's comment field. |

The plugin sends the image as a base64-encoded `image_url` content block, which is standard across all OpenAI-compatible vision APIs.

**Example with Ollama:**
```
# Pull a vision model
ollama pull llava

# Start Ollama (it listens on port 11434 by default)
ollama serve
```
Then set API Base URL to `http://localhost:11434` and Model Name to `llava`.

### Warning
As this plugin uses an external API, we cannot assure you that your data will not be used or sold. I recommend you to check the data policy of each external API you use with this plugin. Using a self-hosted model keeps all image data on your own infrastructure.