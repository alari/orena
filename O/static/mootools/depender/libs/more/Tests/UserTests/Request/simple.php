<?php 
sleep($_GET['sleep']);
if (isset($_GET['num'])) echo 'requested: '.$_GET['num'];
else echo 'ajax request successful';