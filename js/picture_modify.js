$(() => {
    
    const PICTURE_ID = $('#pictureModify')
        .attr('action')
        .match(/page=photo-(\d+)/)[1];

    $('#generateTag').on('click', generateTags);
    $('#applyGeneratedTag').on('click', applyGeneratedTags)

    $('#tr-robot-button').on('click',function() {
        $('.tr-dropdown').toggle();
    })
    
    async function generateTags() {
        $('.tr-dropdown').attr('data-panel', 'loading');
        $('#tr-robot-button').addClass('loading');

        try {
            let tags = await generateTagsFromAPI(
                ACTUAL_API,
                $('#tr-language').val(), 
                PICTURE_ID,
                $('#tr-limit').val()
            );

            $('#tr-robot-button').removeClass('loading');
            $('.tr-dropdown').attr('data-panel', 'select');
    
            $('.tr-tag-container').html('')
    
            tags.forEach(tag => {
                createTagBox(tag);
            });
        } catch(err) {
            $('#trMessage')
                .html(str_there_is_an_error);
            $('#trMessage').addClass('tr-error');

            $('#tr-robot-button').removeClass('loading');

            $('.tr-dropdown').attr('data-panel', 'default');

            $('#generateTag').addClass('tr-disabled');

            $('#tr-robot-button').addClass('dead');
            console.error(err.message);
        };


    }

    function createTagBox(tag) {

        const newTagBox = $('.tr-tag-box.tr-template').clone()

        newTagBox.html(tag);

        newTagBox.removeClass('tr-template');

        $('.tr-tag-container').append(newTagBox);

        newTagBox.on('click', function() {
            $(this).attr(
                "data-selected",
                ($(this).attr("data-selected") == "0")? "1":"0"
            )
        })
    }

    async function applyGeneratedTags() {

        $('.tr-dropdown').attr('data-panel', 'loading');

        tags = [];

        $('.tr-tag-box[data-selected=1]').each((id, el) => {
            tags.push($(el).html());
        })

        await createAndAssignTags(tags, PICTURE_ID);

        window.location.search += '&tagRecog=true';
    }

    $('.tr-action-cancel').on('click', resetDropdown);

    $('.tr-dropdown-close').on('click', () => $('.tr-dropdown').hide());
    
    function resetDropdown() {
        has_loaded = false;
        $('.tr-panel-default .tr-panel-hint').html(str_loading);
        $('#generateTag').addClass('tr-disabled');
        $('.tr-dropdown').attr('data-panel', 'default');
        $('.tr-dropdown').hide();
    }
})