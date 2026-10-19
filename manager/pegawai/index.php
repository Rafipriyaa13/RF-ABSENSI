<?php
require_once('../../includes/Functions.php');
require_once('../../includes/swal.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
     <?php 
 show_flash_swal();
    
    if (!empty($errors)) {
        render_swal('error', 'Gagal!', $errors);
    }
    ?>
</body>
</html>