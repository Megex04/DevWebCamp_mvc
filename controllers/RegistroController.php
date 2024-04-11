<?php

namespace Controllers;

use Model\Paquete;
use Model\Registro;
use Model\Usuario;
use MVC\Router;

class RegistroController {

    public static function crear(Router $router) {
        if(!is_auth()) {
            header('Location: /');
        }

        // verificar si el usuario ya existe
        $registro = Registro::where('usuario_id', $_SESSION['id']);
        if(isset($registro) && $registro instanceof Registro && $registro->paquete_id === "3") {
            header('Location: /boleto?id=' . urlencode($registro->token));
        }

        $router->render('registro/crear', [
            'titulo' => 'Finalizar registro'
        ]);
    }

    public static function gratis(Router $router) {

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            if(!is_auth()) {
                header('Location: /login');
            }
            // verificar si el usuario ya existe
            $registro = Registro::where('usuario_id', $_SESSION['id']);
            if(isset($registro) && $registro instanceof Registro && $registro->paquete_id === "3") {
                header('Location: /boleto?id=' . urlencode($registro->token));
            }

            $token = substr(md5(uniqid(rand(), true)), 0, 8);
            
            // crear registro
            $datos = array(
              'paquete_id' => 3,
              'pago_id' => '',
              'token' => $token,
              'usuario_id' => $_SESSION['id']
            );
            $registro = new Registro($datos);
            $resultado = $registro->guardar();

            if($resultado) {
                header('Location: /boleto?id=' . urlencode($registro->token));
            }
        }
        
    }
    public static function boleto(Router $router) {

        // vALIDAR EN LA URL
        $id = $_GET['id'];

        if(!$id || !strlen($id) === 8) {
            header('Location: /');
        }
        // buscarlo en la BD
        $registro = Registro::where('token', $id);
        if(!$registro) {
            header('Location: /');
        }
        if($registro instanceof Registro) {
            // rellenar las tablas de referencia
            $registro->usuario = Usuario::find($registro->usuario_id);
            $registro->paquete = Paquete::find($registro->paquete_id);
        }

        $router->render('registro/boleto', [
            'titulo' => 'Asistencia a DevWebCamp',
            'registro' => $registro
        ]);
    }
    public static function pagar(Router $router) {

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            if(!is_auth()) {
                header('Location: /login');
            }
            
            // validar que POST no venga vacio
            if(empty($_POST)) {
                echo json_encode([]);
                return;
            }

            $datos = $_POST;
            $datos['token'] =  substr(md5(uniqid(rand(), true)), 0, 8);
            $datos['usuario_id'] = $_SESSION['id'];

            try {
                $registro = new Registro($datos);
                $resultado = $registro->guardar();
                echo json_encode($resultado);
            } catch (\Throwable $th) {
                echo json_encode([
                    'resultado' => 'error'
                ]);
            }
        }
        
    }
}