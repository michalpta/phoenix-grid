<div class="phoenix_grid_pagination" style="padding-bottom: 5px;">
    <a href="#" class="phoenix_grid_reset_button">[ reset ]</a>
</div>
<div style="height: 100%; margin-bottom: 5px;">
    <table>
        <thead>
            <tr>
                <?
                foreach ($column_headers as $header):
                    if ($header['field_id'] != 'phoenix_grid_hidden_column'):
                        ?>
                        <th>
                            <a href="javascript:void(0)" id="<?= $header['field_id'] ?>" class="phoenix_grid_column_header<? if (isset($filters[$header['field_id']]) || isset($searches[$header['field_id']])) echo ' filtered'; ?>" style="display: block; font-size: 14px;"><?= isset($header['field_name']) ? $header['field_name'] : $header['field_id'] ?><? if (isset($sorting[$header['field_id']])) if ($sorting[$header['field_id']] == 1) echo ' &uarr;'; elseif ($sorting[$header['field_id']] == 2) echo ' &darr;';  ?></a>
                        </th>
                        <?
                    endif;
                endforeach;
                ?>
                <th style="border: 0; width: 15px; background: none;">&nbsp</th>
            </tr>
        </thead>
    </table>
</div>
<div>No data available.</div>
</div>