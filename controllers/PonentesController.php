<?php

namespace Controllers;

use Model\Ponente;
use MVC\Router;
use Intervention\Image\ImageManagerStatic as Image;

class PonentesController {

    public static function index(Router $router) {
        $ponentes = Ponente::all();

        if(!is_admin()) {
            header('Location: /login');
        }

        $router->render('admin/ponentes/index', [
            'titulo' => 'Ponentes / Conferencistas',
            'ponentes' => $ponentes
        ]);
    }
    public static function crear(Router $router) {
        if(!is_admin()) {
            header('Location: /login');
        }

        $alertas = [];

        $ponente = new Ponente();
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            if(!is_admin()) {
                header('Location: /login');
            }
            // leer imagen
            if(!empty($_FILES['imagen']['tmp_name'])){
                
                $carpeta_imagenes = '../public/img/speakers';

                // LA CREA SI NO EXISTE
                if(!is_dir($carpeta_imagenes)){
                    mkdir($carpeta_imagenes, 0755, true);
                }

                $imagen_png = Image::make($_FILES['imagen']['tmp_name'])->fit(800, 800)->encode('png', 80);
                $imagen_webp = Image::make($_FILES['imagen']['tmp_name'])->fit(800, 800)->encode('webp', 80);

                $nombre_imagen = md5(uniqid(rand(), true));
                $_POST['imagen'] = $nombre_imagen;
            }
            $_POST['redes'] = json_encode($_POST['redes'], JSON_UNESCAPED_SLASHES);

            $ponente->sincronizar($_POST);

            // validar
            $alertas = $ponente->validar();
            //GUARDAR EL REGISTRO
            
            if(empty($alertas)){

                // GUARDAR LAS IMAGENES
                $imagen_png->save($carpeta_imagenes . '/' . $nombre_imagen . '.png');
                $imagen_webp->save($carpeta_imagenes . '/' . $nombre_imagen . '.webp');

                // GUADAR EN LA BD
                $resultado = $ponente->guardar();
                if($resultado){
                    header('Location: /admin/ponentes');
                }
            }

        }

        $router->render('admin/ponentes/crear', [
            'titulo' => 'Registrar ponente',
            'alertas' => $alertas,
            'redes' => json_decode($ponente->redes)
        ]);
    }
    public static function editar(Router $router) {
        if(!is_admin()) {
            header('Location: /login');
        }
        $alertas = [];
        // validar el Id
        $id = $_GET['id'];
        $id = filter_var($id, FILTER_VALIDATE_INT);

        if(!$id){
            header('Location: /admin/ponentes');
        }

        // obtener ponente a editar
        $ponente = Ponente::find($id);
        if(!$ponente){
            header('Location: /admin/ponentes');
        }
        if($ponente instanceof Ponente) {
            
            $ponente->imagen_actual = $ponente->imagen;

            $redes = json_decode($ponente->redes);
        }
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            if(!is_admin()) {
                header('Location: /login');
            }
            
            // leer imagen
            if(!empty($_FILES['imagen']['tmp_name'])){
                
                $carpeta_imagenes = '../public/img/speakers';

                // LA CREA SI NO EXISTE
                if(!is_dir($carpeta_imagenes)){
                    mkdir($carpeta_imagenes, 0755, true);
                }

                $imagen_png = Image::make($_FILES['imagen']['tmp_name'])->fit(800, 800)->encode('png', 80);
                $imagen_webp = Image::make($_FILES['imagen']['tmp_name'])->fit(800, 800)->encode('webp', 80);

                $nombre_imagen = md5(uniqid(rand(), true));
                $_POST['imagen'] = $nombre_imagen;
            } else {
                if($ponente instanceof Ponente) {
                    $_POST['imagen'] = $ponente->imagen_actual;
                }
            }
            $_POST['redes'] = json_encode($_POST['redes'], JSON_UNESCAPED_SLASHES);

            $ponente->sincronizar($_POST);
            
            $alertas = $ponente->validar();

            if(empty($alertas)) {
                if(isset($nombre_imagen)){
                    
                    $imagen_png->save($carpeta_imagenes . '/' . $nombre_imagen . '.png');
                    $imagen_webp->save($carpeta_imagenes . '/' . $nombre_imagen . '.webp');
                }

                $resultado = $ponente->guardar();
                if($resultado){
                    header('Location: /admin/ponentes');
                }
            }
            
        }
        
        $router->render('admin/ponentes/editar', [
            'titulo' => 'Editar ponente',
            'alertas' => $alertas,
            'ponente' => $ponente,
            'redes' => $redes
        ]);
    }
    public static function eliminar() {
    
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            if(!is_admin()) {
                header('Location: /login');
            }

            $id = $_POST['id'];
            $ponente = Ponente::find($id);
            if(!isset($ponente)){
                header('Location: /admin/ponentes');
            }
            $resultado = $ponente->eliminar();
            if($resultado){
                header('Location: /admin/ponentes');
            }
        }
    }
}