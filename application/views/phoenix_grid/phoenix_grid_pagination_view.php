<small>
<a href='#' class='phoenix_grid_page_button' id='phoenix_grid_<?=$id?>_page_1'>First</a> 
<? 
	for($i=$page_current-14;$i<=$page_current+14;$i++):
	if ($i>0 and $i<=$page_count):
?>
	<a href='#' class='phoenix_grid_page_button' id='phoenix_grid_<?=$id?>_page_<?=$i?>'><? if($i==$page_current) {echo '<b><big>'.$i.'</big></b>';} else {echo $i;} ?></a>
<?
	endif;
	endfor; 
?>
<a href='#' class='phoenix_grid_page_button' id='phoenix_grid_<?=$id?>_page_<?=$page_count?>'>Last</a> 
of <?=$page_count?> pages (<?=$all_results_count?> records)
</small>