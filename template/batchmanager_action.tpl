{combine_css id="tag_recognition.general_css" path=$TR_PATH|cat:"css/general.css"}
{combine_css id="tag_recognition.icon_css" path=$TR_PATH|cat:"fontello/css/tr-icon.css"}


<div class="tr-batch-manager {if $themes[1].id eq "roma"} dark-mode {/if}">
    <div class="tr-batch-manager-info">
        <i class="tr-icon-robot"></i>
        <p>{'Applies auto-generated tags to images'|@translate}</p>
    </div>

    <div class="tr-batch-manager-input">
        <div class="tr-input-container-dropdown">
            <label for='tr-limit'>{'Number of tags'|@translate}</label>
            <input id="tr-limit" name="tr-limit" type="number" min="1" max="100" value="5">
        </div>

        <div class="tr-input-container-dropdown">
            <label for='tr-language'>{'Language code'|@translate}</label>
            <input id="tr-language" name="tr-language"  type="text" value="{$USER_LANG}">
        </div>
    </div>
</div>