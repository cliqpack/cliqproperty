<?php
 namespace App\Repositories\Contructs;

 interface UserContruct {
     
    public function store(array $user);
    public function store_in_manager(array $user);
    public function store_other(array $user);
    public function reg_other(array $user);
    public function findUserInfo();
    public function findUser(array $user);
    public function findVerified($id);
    public function forgotPassword($user);
    public function resetPassword($id);

 }

