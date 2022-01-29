# AutoTag

Piwigo plugin to suggest tags on image (using external API). Internal Name : `tag_recognition`.

Generate tags, choose them and apply them on the image page.

Generate and automatically apply tags to a bunch of image on the batch manager. 

## Available APIs
- [Imagga](https://imagga.com)
- **New** [Microsft Azure](https://azure.microsoft.com/fr-fr/services/cognitive-services/computer-vision/) (you'll have to create an application on Azure)

## Usage
* Install the plugin on your Piwigo
* Create an Imagga Account on https://imagga.com/auth/signup (or [here](https://azure.microsoft.com/fr-fr/free/cognitive-services/) for Microsoft Azure)
* Enter your api token and api secret on the plugin configuration page
* Go to an image modification page and click on the little robot next to the tags input
* Generate tags, select the ones you want and apply them

### Warning
As this plugin use an external API, we cannot assure you that your data will not be used or sold. I recommend you to check the data policy of each external API you use with this plugin. 