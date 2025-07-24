<?php

class TickController extends Controller{
    //public function index(string $year, string $month, string $day, string $hour, string $minute, string $second){
    public function index(int $id){
        $tickModel = new TickModel();
        $vars = $tickModel->get($id);
        $this->render('tick.php', $vars);
    }
}