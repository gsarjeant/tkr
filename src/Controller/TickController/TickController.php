<?php

class TickController extends Controller{
    // every tick is identified by its timestamp
    public function index(string $year, string $month, string $day, string $hour, string $minute, string $second){
        $model = new TickModel();
        $tick = $model->get($year, $month, $day, $hour, $minute, $second);       
        $this->render('tick.php', $tick);
    }
}