<?php

namespace Controllers;

use Model\Categoria;
use Model\Dia;
use Model\Evento;
use Model\EventosRegistros;
use Model\Hora;
use Model\Paquete;
use Model\Ponente;
use Model\Regalo;
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

        if($registro instanceof Registro  && $registro->paquete_id === "1") {
            header('Location: /finalizar-registro/conferencias');
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
            $datos = [
              'paquete_id' => 3,
              'pago_id' => '',
              'token' => $token,
              'usuario_id' => $_SESSION['id']
            ];
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
    public static function conferencias(Router $router) {

        if(!is_auth()) {
            header('Location: /login');
        }

        // validar que el usuario tenga el plan presencial
        $usuario_id = $_SESSION['id'];
        $registro = Registro::where('usuario_id', $usuario_id);

        if($registro instanceof Registro && $registro->paquete_id !== "1") {
            header("Location: /");
        }

        // redireccionar a boleto virtual en caso de haber finalizado el registro
        if($registro instanceof Registro && isset($registro->regalo_id)) {
            header('Location: /boleto?id=' . urlencode($registro->token));
        }
        
        $eventos = Evento::ordenar('hora_id', 'ASC');
        
        $eventos_formateados = [];
        foreach($eventos as $evento) {
            if($evento instanceof Evento) {
            
                $evento->categoria = Categoria::find($evento->categoria_id);
                $evento->dia = Dia::find($evento->dia_id);
                $evento->hora = Hora::find($evento->hora_id);
                $evento->ponente = Ponente::find($evento->ponente_id);
                
                if($evento->dia_id === '1' && $evento->categoria_id === '1') {
                    $eventos_formateados['conferencias_v'][] = $evento;
                }
                if($evento->dia_id === '1' && $evento->categoria_id === '2') {
                    $eventos_formateados['conferencias_s'][] = $evento;
                }
                if($evento->dia_id === '2' && $evento->categoria_id === '1') {
                    $eventos_formateados['workshops_v'][] = $evento;
                }
                if($evento->dia_id === '2' && $evento->categoria_id === '2') {
                    $eventos_formateados['workshops_s'][] = $evento;
                }
            }
        }
        $regalos = Regalo::all('ASC');

        // manejando el registro mediante $_POST
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // revisar  que el usuario este autenticado
            if(!is_auth()) {
                header('Location: /login');
            }
            $eventos = explode(',', $_POST['eventos']);
            if(empty($eventos)) {
                echo json_encode(['resultado' => false]);
                return;
            }
            // obtener el registro del usuario
            $registro = Registro::where('usuario_id', $_SESSION['id']);
            if(!isset($registro) || ($registro instanceof Registro && $registro->paquete_id !== "1") ) {
                echo json_encode(['resultado' => false]);
                return;
            }

            $eventos_array = [];
            // validar la disponibilidad de los eventos seleccionados
            foreach($eventos as $evento_id) {
                $evento = Evento::find($evento_id);

                if($evento instanceof Evento) {
                    // comprobar que el evento exista
                    if(!isset($evento) || $evento->disponibles ===  0) {
                        echo json_encode(['resultado' => false]);
                        return;
                    }
                }
                $eventos_array[] = $evento;
            }

            foreach($eventos_array as $evento) {
                if($evento instanceof Evento) {
                    $evento->disponibles -= 1;
                    $evento->guardar();
                }
                // almacenar el registro
                $datos = [
                  'evento_id' => (int) $evento->id,
                  'registro_id' => (int) $registro->id
                ];
                
                $registro_usuario = new EventosRegistros($datos);
                $registro_usuario->guardar();
            }

            // almacenar el regalo
            $registro->sincronizar(['regalo_id' => $_POST['regalo_id']]);
            $resultado = $registro->guardar();

            if($resultado) {
                if($registro instanceof Registro) {
                    echo json_encode([
                        'resultado' => $resultado,
                        'token' => $registro->token
                    ]);
                } else {
                    echo json_encode([]);
                }
            } else {
                echo json_encode(['resultado' => false]);
            }
            return;
        }

        $router->render('registro/conferencias', [
            'titulo' => 'Elige entre workshops y conferencias',
            'eventos' => $eventos_formateados,
            'regalos' => $regalos
        ]);
    }
}