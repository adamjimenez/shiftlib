<?php
$this->set_section($this->section, $_GET['id'], ['read']);
?>

<table border="0" cellspacing="0" cellpadding="5" width="100%">
<?php
foreach ($vars['fields'][$this->section] as $name => $type) {
    $label = $vars['label'][$this->section][$name];

    if (!$label) {
        $label = ucfirst(str_replace('_', ' ', $name));
    }
    
    $value = $this->get_value($name);
    
    if (!$value) {
        continue;
    } ?>

    <tr>
        <th align="left" valign="top" width="20%"><?=$label; ?></th>
        <td>
            <?=$value; ?>
        </td>
    </tr>
<?php
} ?>
</table>

<span data-section="<?=$this->section;?>" <?php if ($this->section !== $_GET['option']) { ?>style="display: none;"<?php } ?>>
    <button class="btn btn-secondary" type="button" onclick="location.href='?option=<?=$this->section; ?>&edit=true&id=<?=$id; ?>&<?=$qs; ?>'" style="font-weight:bold;"><i class="fas fa-pencil-alt"></i></button>
    
    <form method="post" style="display:inline;">
        <input type="hidden" name="delete" value="1">
        <input type="hidden" name="section" value="<?=$this->section;?>">
        <button class="btn btn-danger" type="submit" onclick="return confirm('are you sure you want to delete?');"><i class="fas fa-trash"></i></button>
    </form>
</span>

<script>
    $('span[data-section="<?=$this->section;?>"]').appendTo( $('.toolbar .holder' ) );
</script>