<?php
//THIS SCRIPT WILL SETUP TABLES USED BY DEFAULT BY MySQLPDODataProvider
//YOU DON'T NEED TO RUN THIS SCRIPT IF YOU DON'T USE MySQLPDODataProvider
//IF YOU USE CUSTOM PROVIDER YOU CAN RUN THIS IS TEST DATABASE TO SEE IMPORTANT FIELDS STRUCTURE
//YOU CAN ALSO RUN THIS ON YOUR EXISTING DB TO ADD TABLES THEN REMOVE hpay_orders TABLE BECUSE PEOPLE USUALY HAVE THERE OWN ..orders TABLE AND KEEP OTHER
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//BEFORE RUNNING THIS SCRIPT FROM CLI SET mysqlpdo_data_provider_... properties in holestpay.ini OF LIB ROOT FOLDER 
//TO RUN FROM SERVER COMMAND CONSOLE: cd to setup_run_once_from_cli folder then execute:
//php mysqlpdo_provider_create_dbtables.php
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 
