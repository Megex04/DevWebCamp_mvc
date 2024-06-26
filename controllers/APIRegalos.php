<?php

namespace Controllers;

use Model\EventoHorario;
use Model\Regalo;
use Model\Registro;

class APIRegalos {

    public static function index() {
        if(!is_admin()) {
            echo json_encode([]);
            return;
        }

        $regalos = Regalo::all();

        // VALIDO SOLO A USUARIOS REGISTRADOS "PRESENCIALES"
        foreach($regalos as $regalo) {
            if($regalo instanceof Regalo) {
                $regalo->total = Registro::totalArray(['regalo_id' => $regalo->id, 'paquete_id' => "1"]);
            }
        }
        echo json_encode($regalos);
        return;
    }
}