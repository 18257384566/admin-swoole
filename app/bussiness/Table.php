<?php
/**
 * Created by PhpStorm.
 * User: ww
 * Date: 2018/10/29
 * Time: 下午4:50
 */
namespace App\Bussiness;

class Table extends BaseBussiness
{
    public function add($pro_no){
        $new_table = 'wallet_'.$pro_no.'_project_coin';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_coin");
        $new_deposit_table = 'wallet_'.$pro_no.'_project_deposit';
        $this->db->query("CREATE TABLE if not exists $new_deposit_table LIKE wallet_project_deposit");
        $new_withdraw_table = 'wallet_'.$pro_no.'_project_log';
        $this->db->query("CREATE TABLE if not exists $new_withdraw_table LIKE wallet_project_log");
        $new_table = 'wallet_'.$pro_no.'_project_phone_code';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_phone_code");
        $new_table = 'wallet_'.$pro_no.'_project_server_wallets_flow';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_server_wallets_flow");
        $new_table = 'wallet_'.$pro_no.'_project_transaction';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_transaction");
        $new_table = 'wallet_'.$pro_no.'_project_transaction_info';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_transaction_info");
        $new_table = 'wallet_'.$pro_no.'_project_user_wallets';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_user_wallets");
        $new_table = 'wallet_'.$pro_no.'_project_wallets';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_wallets");
        $new_table = 'wallet_'.$pro_no.'_project_wallets_assets';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_wallets_assets");
        $new_table = 'wallet_'.$pro_no.'_project_wallets_batch';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_wallets_batch");
        $new_table = 'wallet_'.$pro_no.'_project_wallets_flow';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_wallets_flow");
        $new_table = 'wallet_'.$pro_no.'_project_withdraw';
        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_withdraw");
//        $new_table = 'wallet_'.$pro_no.'_project_admin_login_info';
//        $this->db->query("CREATE TABLE if not exists $new_table LIKE wallet_project_admin_login_info");
    }



}