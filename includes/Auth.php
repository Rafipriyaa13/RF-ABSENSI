<?php

require_once __DIR__ . '/Functions.php';

function sudah_login():bool
{
    return !empty($_SESSION['user_id']);
}

function dashboard_url(string $role): string
{
    return $role === 'manager' ?  '/manager/dashboard.php' : '/pegawai/dashboard.php';
}

function cek_login():void
{
    if(!sudah_login()){
        header('Location: /auth/login.php');
        exit;
    }
}

function cek_role(string $role): void
{
    cek_login();
    if($_SESSION['role']!==$role){
        header('Location: '. dashboard_url($_SESSION['role']));
        exit;
    }
}


