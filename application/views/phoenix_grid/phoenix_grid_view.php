<div style="background-color: #eee; background-image: url('<?= base_url() ?>assets/images/bg_grain.png'); padding: 5px; margin: 5px 0px; border: 1px solid #ddd;">
    <div class="phoenix_grid" id="phoenix_grid_<?= $phoenix_grid_id ?>">

        <div class="phoenix_grid_page">
            <div class="phoenix_grid_progressbar" style="min-width: 400px; margin: 5px 0px 6px 0px; height: 1em; display: none;"></div>
            <script>
                $("#phoenix_grid_<?= $phoenix_grid_id ?>").find('.phoenix_grid_progressbar').progressbar({value: false}).fadeIn();
            </script>
        </div>
        <div class="phoenix_grid_option_box" style="width: 200px; display: none; padding: 5px; position: absolute; z-index: 15; top: 0; left: 0; background: whitesmoke; border: 1px solid #ddd;">
            <div style="line-height: 10px;"><input type='text' class='phoenix_grid_searchbox' style="width: 140px; margin-right: 5px;"/> <a class="phoenix_grid_sortbutton" href="javascript:void(0)">sort</a> <a class='phoenix_grid_sortclear' title='Clear sort' style="text-decoration: none;" href='javascript:void(0)'>&#x2716;</a></div>
            <div class="phoenix_grid_filterbox" style="width: 100%; max-height: 200px; overflow: auto; margin: 3px 0px;">loading...</div>
            <div><input type='button' class='phoenix_grid_filterbutton' value='OK' style="width: 100px;" /> <a title='Clear filter' href='javascript:void(0)'  style="text-decoration: none;" class='phoenix_grid_filterclear'>&#x2716;</a></div>
        </div>
    </div>
</div>