<?php
/**
 * Esta clase es la versión de CFormulario con estilos de bootstrap
 * @package sistema.web.asistentes
 * @author Jorge Alejandro Quiroz Serna (Jako) <alejo.jko@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2016, jakop 
 */
class CBForm extends CFormulario{
    /**
     * Esta función permite generar un campo de texto con estilos de bootstrap
     * @param CModelo $modelo
     * @param string $atributo
     * @param array $opciones
     * @return string
     */
    public function campoTexto($modelo = null, $atributo = '', $opciones = array()) {
        $opHtml = $this->obtenerOpciones($modelo, $atributo, $opciones);
        return CBoot::text($modelo->$atributo, $opHtml);
    }
    
    /**
     * Esta función permite generar un area de texto con estilos de bootstrap
     * @param CModelo $modelo
     * @param string $atributo
     * @param array $opciones
     * @return string
     */
    public function areaTexto($modelo = null, $atributo = '', $opciones = array()) {
        $opHtml = $this->obtenerOpciones($modelo, $atributo, $opciones);
        return CBoot::textArea($modelo->$atributo, $opHtml);
    }
    /**
     * Esta función permite generar una lista de selección con estilos de bootstrap
     * @param CModelo $modelo
     * @param string $atributo
     * @param array $elementos
     * @param array $opciones
     * @return string
     */
    public function lista($modelo = null, $atributo = '', $elementos = array(), $opciones = array()) {
        $opHtml = $this->obtenerOpciones($modelo, $atributo, $opciones);
        return CBoot::select($modelo->$atributo, $elementos, $opHtml);
    }
}
