<?php

class TickController extends Controller{
    //public function index(string $year, string $month, string $day, string $hour, string $minute, string $second){
    public function index(int $id){
        global $app;
        
        $tickModel = new TickModel($app['db'], $app['config']);
        $vars = $tickModel->get($id);
        $this->render('tick.php', $vars);
    }
}