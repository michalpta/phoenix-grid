<div class="phoenix_grid_pagination" style="padding-bottom: 5px;">
    <a title="Export to Excel" href="#" class="phoenix_grid_export_button"><img src="<?= base_url() ?>assets/images/attachment_icons/xls.png" style="vertical-align: middle;" /></a>&nbsp;<a title="Export to CSV" href="#" class="phoenix_grid_export_button2"><img src="<?= base_url() ?>assets/images/attachment_icons/csv.png" style="vertical-align: middle;" /></a>&nbsp;<?= $pagination ?>&nbsp; <a href="#" class="phoenix_grid_reset_button">[ reset ]</a>
</div>
<div class="phoenix_grid_progressbar" style="margin: 5px 0px 6px 0px; height: 1em; display: none;"></div>
<div class="phoenix_grid_table" style=" height: 100%; overflow-x: auto; overflow-y: hidden; padding-bottom: 16px;">

    <table>
        <thead>
            <tr>
                <?
                foreach ($column_headers as $header):
                    if ($header['field_id'] != 'phoenix_grid_hidden_column'):
                        ?>
                        <th>
                            <a href="javascript:void(0)" id="<?= $header['field_id'] ?>" class="phoenix_grid_column_header<? if (isset($filters[$header['field_id']]) || isset($searches[$header['field_id']])) echo ' filtered'; ?>" style="display: block; font-size: 14px;"><?= isset($header['field_name']) ? $header['field_name'] : $header['field_id'] ?><? if (isset($sorting[$header['field_id']])) if ($sorting[$header['field_id']] == 0) echo ' &uarr;'; elseif ($sorting[$header['field_id']] == 1) echo ' &darr;';  ?></a>
                        </th>
                        <?
                    endif;
                endforeach;
                ?>
                <!--<th style="border: 0; width: 15px; background: none;">&nbsp</th>-->
            </tr>
        </thead>
        <tbody>
            <? foreach ($result as $row): ?>
                <tr>
                    <?
                    foreach ($row as $key => $item):
                        if (isset($column_headers[$key])):
                            ?>
                            <td><div style="max-width: 400px; overflow: hidden;"><?= $item ? $item : '&nbsp;' ?></div></td>
                                <?
                            endif;
                        endforeach;
                        ?>
                    <!--<td style="border: 0; width: 15px; background: none;">&nbsp</td>-->
                </tr>
            <? endforeach; ?>
        </tbody>
    </table>
    <!--
    <? //var_dump($column_headers); ?>
    <style>
            .header {
                    font: bold 11px "Arial Narrow", "Trebuchet MS", Verdana, Arial, Helvetica,
                    sans-serif;
                    color: #000;
                    border: 1px solid #C1DAD7;
                    text-transform: uppercase;
                    text-align: left;
                    padding: 9px 6px 9px 6px;
                    background: #CAE8EA url('<?= base_url() ?>assets/images/bg_header.jpg') no-repeat;
                    white-space:nowrap;
            }
    </style>
    <div class="page" style="width: 2000px;">
    <?
    foreach ($column_headers as $key => $header):
        if (substr($key, 0, 6) != "hidden"):
            ?>
                            <div class="column" style="float: left; margin: 0px 1px; padding: 0px 2px; background: white;">
                                            <p style="margin: 0px; cursor: pointer;" id="<?= $header['field_id'] ?>" class="phoenix_grid_column_header header"><?= $header["field_name"] ?>&nbsp;</p>
            <?
            foreach ($result as $row):
                ?>
                                                            <p style="white-space: nowrap;"><?= $row[$key] ?>&nbsp;</p>
                <?
            endforeach;
            ?>
                            </div>
            <?
        endif;
    endforeach;
    ?>
    </div>
    -->
</div>