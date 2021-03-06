<?php
/**
 * Esta clase maneja todo lo que tenga que ver con recursos de la aplicación
 * @package sistema.manejadores
 * @author Jorge Alejandro Quiroz Serna (jako) <alejo.jko@gmail.com>
 * @version 1.0.2
 * @copyright (c) 2015, jakop
 */
class CMRecursos {
    const RE_CSS = 'css';
    const RE_JS = 'js';
    const POS_BODY = 0;
    const POS_HEAD = 1;
    const POS_READY = 2;
    
    /**
     * Ruta donde se alojarán los recursos en la aplicación
     * @var string 
     */
    private $rutaRecursos;
    /**
     * Url de los recursos en la aplicación
     * @var string 
     */
    private $urlRecursos;
    /**
     * Scripts registrados para ser mostrados en el cliente
     * @var string 
     */
    private $scriptsEnCliente = [];
    /**
     * Estilos registrados para ser mostradose en el cliente
     * @var string 
     */
    private $estilosEnCliente = [];
    
    /**
     * Todos los recursos que se registran
     * @var array 
     */
    private $recursosRegistrados = [];
    /**
     * Lista de recursos que deben ser incorporados antes que los demás
     * @var array 
     */
    private $recursosPrimarios = [];
    /**
     * Esta variable se usa para almacenar los alias que se registran,
     * de esta manera no solo se controla que alias ya existen, sin
     * @var array 
     */
    private $aliasRegistrados = [];
    /**
     * Ruta de donde se toman los recursos del sistema
     * @var string 
     */
    private $fuenteRecursos;
    
    public function __construct() {
        $this->rutaRecursos = Sistema::resolverRuta('!raiz.recursos');
        $this->urlRecursos = Sistema::apl()->urlBase.'recursos/';
        $this->fuenteRecursos = Sistema::resolverRuta('!sistema.recursos');
        $this->inicializar();
    }
    
    /**
     * Está función inicializa todos lo necesario para esta clase
     */
    private function inicializar(){
        // si no existe la ruta de recursos la creamos
        if(!file_exists($this->rutaRecursos) || !is_dir($this->rutaRecursos)){
            mkdir($this->rutaRecursos);
        }
        $this->recursosRegistrados = [
            'js' => [],
            'css' => [],
        ];
    }
    
    /**
     * Esta función toma un recurso, copia el archivo fuente y lo pega en 
     * la carpeta destino (carpeta de recursos de la aplicación) y registra el
     * archivo
     * @param array $recurso
     * @param string $tipo
     * @return boolean
     */
    private function registrarRecurso($recurso = [], $tipo = self::RE_JS, $primario = false){
        if(!isset($recurso['url']) && (!isset($recurso['ruta']) || !file_exists($recurso['ruta']))){
            return false;
        }
        $archivo = isset($recurso['ruta'])? basename($recurso['ruta']) : '';
        # si se seteó la posición url quiere decir que no se quiere mover el archivo
        if(!isset($recurso['url']) && !file_exists($this->rutaRecursos.DS.$tipo.DS.$archivo)){            
            $this->moverRecurso($recurso['ruta'], $archivo, $tipo);
        } else if(isset ($recurso['url'])){
            $urlRecurso = $recurso['url'];
        } 
        
        if(!isset($urlRecurso)) {
            $urlRecurso = $this->urlRecursos."$tipo/$archivo";
        }
        if($primario){
            $this->recursosPrimarios[$tipo][] = array(
                'alias' => $recurso['alias'],
                'url' => $urlRecurso,
                'pos' => isset($recurso['pos'])? $recurso['pos'] : self::POS_HEAD,
                'tipo' => $tipo, # vuelvo y guardo el tipo por que más adelante me es util
            );
        } else {            
            $this->recursosRegistrados[$tipo][] = array(
                    'alias' => $recurso['alias'],
                    'url' => $urlRecurso,
                    'pos' => isset($recurso['pos'])? $recurso['pos'] : self::POS_HEAD,
                    'tipo' => $tipo, # vuelvo y guardo el tipo por que más adelante me es util
                );
            $this->aliasRegistrados[$tipo][] = $recurso['alias'];
        }
        return true;
    }
    
    /**
     * Esta función copia un asset de la ruta fuenta a la ruta destino (ruta de los recursos en la aplicación)
     * @param string $de ruta fuente del archivo
     * @param string $archivo nombre del archivo
     * @param string $carpeta carpeta donde se alojará el recurso
     * @return boolean
     */
    private function moverRecurso($de, $archivo, $carpeta){
        $rutaGuardar = $this->rutaRecursos;
        if(!file_exists($rutaGuardar.DS.$carpeta)){
            mkdir($rutaGuardar.DS.$carpeta);
        }
        $rutaGuardar.= DS.$carpeta.DS.$archivo;
        return copy(realpath($de), $rutaGuardar);
    }
    
    /**
     * Esta función permite registrar un archivo js para ser incluido en la aplicación
     * @param array $recurso
     * @return boolean
     */
    public function registrarRecursoJS($recurso = [], $primario = false){
        # verificamos si el recurso ya fue registrado con ese alias, si es así no realizamos el registro del recurso        
        if(isset($this->aliasRegistrados[self::RE_JS]) && 
            $this->getJs($recurso['alias']) !== false){
            return false;
        }
        return $this->registrarRecurso($recurso, self::RE_JS, $primario);
    }
    
    /**
     * Esta función permite registrar un archivo css para ser incluido en la aplicación
     * @param string $recurso
     * @return boolean
     */
    public function registrarRecursoCSS($recurso = [], $primario = false){
        # verificamos si el recurso ya fue registrado con ese alias, si es así no lo incluimos
        if(isset($this->aliasRegistrados[self::RE_CSS]) && 
            $this->getCss($recurso['alias']) !== false){
            return false;
        }
        $recurso['pos'] = self::POS_HEAD;
        return $this->registrarRecurso($recurso, self::RE_CSS, $primario);
    }
    
    /**
     * Esta función permite registrar código js para que sea incluido en la aplicación
     * @param string $script
     * @param int $pos
     */
    public function registrarScriptCliente($script, $pos = self::POS_BODY){
        $this->scriptsEnCliente[] = array(
            'script' => $script,
            'pos' => $pos,
        );
    }
    
    /**
     * Esta función permite registrar estilos para ser incluidos en la aplicación
     * @param string $estilos
     */
    public function registrarEstilosCliente($estilos){
        $this->estilosEnCliente[] = array(
            'estilos' => $estilos,
            'pos' => self::POS_HEAD,
        );
    }
    
    /**
     * Esta función agrega los scripts y estilos registrados a la salida html de una vista
     * @param string $html
     */
    public function incluirRecursos(&$html){
        $recursos = $this->construirHtmlRecursos();    
        $head = '';
        $body = '';
        $scriptsbody = ''; $scriptshead = ''; $scriptsready = ''; $estiloshead = '';
        foreach ($recursos AS $pos=>$recurso){
            $codigo = implode('', $recurso);
            if($pos === self::POS_BODY){ $body .= $codigo;}
            else if($pos === self::POS_HEAD){ $head .= implode('', $recurso);}
            else if($pos === self::POS_BODY + 3){ $scriptsbody .= implode('', $recurso);}
            else if($pos === self::POS_HEAD + 3){ $scriptshead .= implode('', $recurso);}
            else if($pos === self::POS_READY + 3){ $scriptsready .= implode('', $recurso);}
            else if($pos === self::POS_HEAD + 5) { $estiloshead .= implode('', $recurso);}
        }
        $sbody = $scriptsbody != ""? '<script type="text/javascript">'.$scriptsbody.'</script>' : '';
        $shead = $scriptshead != ""? '<script type="text/javascript">'.$scriptshead.'</script>' : '';
        $sready = $scriptsready != ""? '<script type="text/javascript">jQuery(function(){'.$scriptsready.'});</script>' : '';
        $ehead = $estiloshead != ""? '<style>'.$estiloshead.'</style>' : '';
        $html = str_replace('</body>', $body.$sbody.$sready.'</body>', 
                str_replace('</head>', $head.$ehead.$shead.'</head>', $html)
            );
    }
    
    /**
     * Esta función construye las etiquetas que se incluirán para cada recurso
     * @return array
     */
    private function construirHtmlRecursos(){
        #si hay recursos primarios los incluimos 
        if(isset($this->recursosPrimarios['css'])){            
            $this->recursosRegistrados['css'] = array_merge($this->recursosPrimarios['css'], $this->recursosRegistrados['css']);
        }
        if(isset($this->recursosPrimarios['js'])){
            $this->recursosRegistrados['js'] = array_merge($this->recursosPrimarios['js'], $this->recursosRegistrados['js']);
        }
        # combinamos todos los recursos para recorrerlos más fácil
        $recursos = array_merge($this->recursosRegistrados['css'], $this->recursosRegistrados['js']);
        $html = [];
        # recorremos todos los recursos y construimos su respectivo html
        foreach($recursos AS $recurso){
            if($recurso['tipo'] == self::RE_JS){ 
                $etiqueta = '<script type="text/javascript" src="' . $recurso['url'] . '"></script>';
            }else{
                $etiqueta = '<link rel="stylesheet" type="text/css" href="' . $recurso['url'] . '">';
            }            
            $html[$recurso['pos']][] = $etiqueta;
        }
        # agregamos los scripts cliente registrados
        foreach ($this->scriptsEnCliente AS $script){ $html[intval($script['pos']) + 3][] = $script['script']; }
        # agregamos los estilos cliente registrados
        foreach ($this->estilosEnCliente AS $estilo){ $html[self::POS_HEAD + 5][] = $estilo['estilos']; }        
        return $html;
    }
    
    /**
     * Esta función permite incluir la librería jQuery desde el sistema
     * @return string
     */
    public function registrarJQuery(){
        return $this->registrarRecursoJS([
            'alias' => 'sis-jquery',
            'ruta' => Sistema::resolverRuta('!sistema.recursos.frameworks.jquery').'/jquery.js',
            'pos' => CMRecursos::POS_HEAD,            
        ], true);
    }
    
    /**
     * Esta función permite incluir la librería awesome font desde el sistema
     */
    public function registrarAwesomeFont(){
        $this->registrarRecursoCSS([
            'alias' => 'sis-awesome-font',
            'ruta' => Sistema::resolverRuta('!sistema.recursos.frameworks.awesome_fonts.css').'/font_awesome.css',
        ], true);
        $rutaFuente = Sistema::resolverRuta('!sistema.recursos.frameworks.awesome_fonts.fonts');
        $rutaDestino = Sistema::resolverRuta('!raiz.recursos.fonts');
        $this->moverDependencias($rutaFuente, $rutaDestino);
    }
    
    /**
     * Esta función permite registrar bootstrap 3 desde el sistema
     */
    public function registrarBootstrap3(){
        $this->registrarRecursoJS([
            'alias' => 'sis-bootstrap-3',
            'ruta' => Sistema::resolverRuta('!sistema.recursos.frameworks.bootstrap3.js').'/bootstrap.js',
            'pos' => CMRecursos::POS_HEAD,
        ], true);
        $this->registrarRecursoCSS([
            'alias' => 'sis-bootstrap-3',
            'ruta' => Sistema::resolverRuta('!sistema.recursos.frameworks.bootstrap3.css').'/bootstrap.css',
        ], true);
        $rutaFuente = Sistema::resolverRuta('!sistema.recursos.frameworks.bootstrap3.fonts');
        $rutaDestino = Sistema::resolverRuta('!raiz.recursos.fonts');
        $this->moverDependencias($rutaFuente, $rutaDestino);
    }
    
    /**
     * Esta función permite recorrer los archivos o depedencias de una librería
     * y moverlos a la aplicación, donde serán más faciles de llamar por medio
     * de la url
     * @param string $fuente
     * @param string $destino
     * @return boolean
     */
    public function moverDependencias($fuente, $destino){
        if(!file_exists($destino) && !is_dir($destino)){ mkdir($destino); }
        
        $archivos = scandir($fuente);        
        $guardado = false;
        foreach ($archivos AS $archivo){
            if(!is_file($fuente.DS.$archivo) || file_exists($destino.DS.$archivo)){ continue; }
            $guardado = copy($fuente.DS.$archivo, $destino.DS.$archivo);
            if(!$guardado){ break; }
        }
        
        return $guardado;
    }
        
    /**
     * Esta función permite obtener la url usando el alias asignado al recurso
     * @param string $alias
     * @return mixed
     */
    public function getJs($alias){
        if (array_search($alias, $this->aliasRegistrados[self::RE_JS]) === false) {
            return false;
        }
        $pos = array_search($alias, $this->aliasRegistrados[self::RE_JS]);
        return $this->recursosRegistrados[self::RE_JS][$pos]['url'];
    }
    
    /**
     * Esta función permite obtener la url usando el alias asignado al recurso
     * @param string $alias
     * @return boolean
     */
    public function getCss($alias){
        if (array_search($alias, $this->aliasRegistrados[self::RE_CSS]) === false) {
            return false;
        }
        $pos = array_search($alias, $this->aliasRegistrados[self::RE_CSS]);
        return $this->recursosRegistrados[self::RE_CSS][$pos]['url'];
    }
    
    /**
     * Esta función retorna todo el array de recursos registrados
     * @return array
     */
    public function getRecursos(){
        return $this->recursosRegistrados;
    }
    
    /**
     * Esta carpeta permite obtener la ruta de la ruta donde se almacenan los recursos
     * @return string
     */
    public function getRutaRecursos(){
        return $this->rutaRecursos;
    }
    
    /**
     * Esta función permite obtener la url de la carpeta destinada a recursos
     * @return string
     */
    public function getUrlRecursos(){
        return $this->urlRecursos;
    }
    /**
     * Esta función retorna los alias de los recursos registrados
     * @return array
     */
    public function getAlias(){
        return $this->aliasRegistrados;
    }
}