{combine_css id="tag_recognition.general_css" path=$TR_PATH|cat:"css/general.css"}
{combine_script id="tag_recognition.api_js" require="jquery" path=$TR_PATH|cat:"js/api.js"}
{combine_script id="tag_recognition.admin_js" require="jquery" load="async" path=$TR_PATH|cat:"js/admin.js"}
{combine_css id="tag_recognition.icon_css" path=$TR_PATH|cat:"fontello/css/tr-icon.css"}

{footer_script}
    const TR_APIS = Object.keys({json_encode($TR_API_LIST)});
    const TR_CONF = {json_encode($TR_API_CONF)};
{/footer_script}

<div class="titrePage">
  <h2>Tag Recognition</h2>
</div>

<div class="tr-conf-page {if $themes[1].id eq "roma"} dark-mode {/if}">

    <div class="tr-api-forms">
    {foreach from=$TR_API_INFO item=apiInfo key=apiName}
    <form method="post" class="tr-api-container" data-api='{$apiName}' {if $TR_API_SELECTED == $apiName} style="order: -1" {/if}>
        {if $TR_API_SELECTED == $apiName} 
        <i class="tr-api-used"> {'This API is used'|translate} </i>
        {/if}

        <a href="{$apiInfo.site}" class="tr-api-link icon-info-circled-1" target="_blank"></a>

        <span class="tr-icon" style="background-image : url('{$apiInfo.icon}')"></span>
        
        {foreach from=$TR_API_PARAMS[$apiName] item=label key=param}
            <div class="tr-input-container" id="tr-input-container-{$param}">
                <label for="user">{$label|@translate}</label>
                <input type="text" id="" name={$param} value="{$TR_API_CONF[$apiName][$param]}">
            </div>
        {/foreach}

        <input type="hidden" name="api" value="{$apiName}">
        <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">
        <input type="submit" name="save"class="tr-button-2" value="{'Save Settings'|translate}">
        <input type="submit" name="use" class="tr-button-1 {if $TR_API_SELECTED == $apiName}tr-disabled{/if}" value="{'Use this API'|translate}">
    </form>
    {/foreach}
    </div>

</div>


