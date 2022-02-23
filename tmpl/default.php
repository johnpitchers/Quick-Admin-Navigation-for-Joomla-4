<?php
	defined('_JEXEC') or die;
?>
<script>
  window.quickadminnavItems = <?= json_encode($menuItems, JSON_PRETTY_PRINT); ?>;
</script>
<div id="qan-app"></div>
