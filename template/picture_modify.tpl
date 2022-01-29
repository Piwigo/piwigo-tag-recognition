{combine_script id="tag_recognition.api_js" require="jquery" load="async" path=$TR_PATH|cat:"js/api.js"}
{combine_script id="tag_recognition.picture_modify_js" require="jquery" load="async" path=$TR_PATH|cat:"js/picture_modify.js"}
{combine_css id="tag_recognition.general_css" path=$TR_PATH|cat:"css/general.css"}
{combine_css id="tag_recognition.icon_css" path=$TR_PATH|cat:"fontello/css/tr-icon.css"}
{combine_css path="admin/themes/default/fontello/css/animation.css" order=10}

{footer_script}
    const pwg_token = "{$PWG_TOKEN}";
    const ACTUAL_API = '{$ACTUAL_API}'
    const str_there_is_an_error = '{'There is an error with the API'|@translate}';
    const str_loading = '{'Loading...'|@translate}';
    const language = null;
{/footer_script}

<div class="tr-dropdown {if $themes[1].id eq "roma"} dark-mode {/if}" data-panel="default">
    <div class="tr-panel tr-panel-default">
        <div class="tr-panel-header">
            <span class="tr-panel-title">{'Generate tags for this image'|@translate}</span>
            <span id="trMessage" class="tr-panel-hint">{'With %s API'|@translate:$ACTUAL_API}</span>
        </div>

        <div class="tr-input-container-dropdown">
            <label for='tr-limit'>{'Maximum number'|@translate}</label>
            <input id="tr-limit" type="number" min="1" max="100" value="10">
        </div>

        <div class="tr-input-container-dropdown">
            <label for='tr-language'>{'Language code'|@translate}</label>
            <input id="tr-language" type="text" value="{$USER_LANG}">
        </div>

        <div id="generateTag" class="tr-button-1">{'Generate tags'|@translate}</div>
    </div>
    <div class="tr-panel tr-panel-select">
        <div class="tr-panel-header">
            <span class="tr-panel-title">{'Proposed tags'|@translate}</span>
            <span class="tr-panel-hint">{'Click on tags to select them'|@translate}</span>
        </div>
        <div class="tr-tag-container">
        </div>
        <div class="tr-dropdown-actions">
            <div id="applyGeneratedTag" class="tr-button-1">{'Apply tags'|@translate}</div>
            <div class="tr-button-3 tr-action-cancel">{'Cancel'|@translate}</div>
        </div>
    </div>
    <div class="tr-panel tr-panel-loading">
        <div class="tr-panel-header">
            <span class="tr-panel-title">{'Loading...'|@translate}</span>
            <span class="tr-panel-hint">{'Fetching the API...'|@translate}</span>
        </div>
    </div>
    <div class="tr-panel tr-panel-succeed">
        <div class="tr-panel-header">
            <span class="tr-panel-title">{'Tags successfully added'|@translate}</span>
        </div>
        <div class="tr-dropdown-actions">
            <div class="tr-button-1 tr-action-cancel">{'Ok'|@translate}</div>
        </div>
    </div>
    <div class="tr-tag-box tr-template" data-selected=0></div>
    <i class="tr-dropdown-close icon-cancel"></i>
</div>

<div class="tr-added-tags"></div>

<i 
    id="tr-robot-button" 
    class="tr-icon-robot{if $themes[1].id eq "roma"} dark-mode {/if}"
    title="Suggest Tags">
</i>