<div style="overflow: auto;">
<table style="">
    <thead>
        <tr>
            <? foreach($column_headers as $header): ?>
                <th>
                    <a id="<?=$header['field_id']?>" class="phoenix_grid_column_header<? if(isset($filters[$header['field_id']]) || isset($searches[$header['field_id']])) echo ' filtered'; ?>" style="display: block; font-size: 14px;"><?=isset($header['field_name'])?$header['field_name']:$header['field_id']?></a><?php
                       /*if ($header==$this->input->post('phoenix_grid_sortheader') && $this->input->post('phoenix_grid_sortdir')=="asc" ) echo "&uarr;";
                       else if ($header==$this->input->post('phoenix_grid_sortheader') && $this->input->post('phoenix_grid_sortdir')=="desc" ) echo "&darr;";
                       else echo "<span style='visibility:hidden;'>&uarr;</span>";*/
                    ?>
                </th>
            <? endforeach; ?>
        </tr>
    </thead>
    <tbody style="overflow: auto;">
        <? foreach($result as $row): ?>
        <tr>
            <? 
			foreach($row as $key=>$item): 
			if (isset($column_headers[$key])): 
			?>
                <td><?=$item?></td>
            <? 
			endif; 
			endforeach; 
			?>
        </tr>
        <? endforeach; ?>
    </tbody>
</table>
</div>
<div class="phoenix_grid_pagination">
	<?= $pagination ?> <a href="#" class="phoenix_grid_reset_button">[ reset ]</a>
</div>