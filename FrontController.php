<?php

function __autoload($class_name) {
    //echo "<br>".$class_name;
    if ($class_name!="") {
        
        if (strpos($class_name,"_")!==false) {
            
            $class_name = explode("_",$class_name);

            $module = $class_name[0];
            $class = $class_name[1];

            if ($module=="Util") $caminho = 'app/util/';
            else if (strpos($class,"Controller")!==false) $caminho = ('app/module/'.$module.'/controller/');
            else if (strpos($class,"Mapper")!==false) $caminho = ('app/module/'.$module.'/model/mapper/');
            else if (strpos($class,"Repository")!==false) $caminho = ('app/module/'.$module.'/model/repository/');
            else $caminho = ('app/module/'.$module.'/model/');

            $caminho .= $class.'.php';


        } else {

            $class = $class_name;
            if (strpos($class,"View")!==false) $caminho = ('app/view/View.php');
            else if (strpos($class,"Abstract")!==false) $caminho = ('app/core/'.$class.".php");

        }
        //echo " > ".$caminho." ! ";
        require_once($caminho);
    }
}

class FrontController {
	
    public $controller;

    public function __construct(){

    //recebe o controller e o action
        $m = (!isset($_GET['m'])) ? "Index" : $_GET['m'];
        $c = (!isset($_GET['c'])) ? "Index" : $_GET['c'];
        $a = (!isset($_GET['a'])) ? "Index" : $_GET['a'];
        
        session_start();

    //conecta com o DB
        $host="remoto";

        if ($host=="local"){
            $conect = mysql_connect("localhost","root","");
            $banco = mysql_select_db("db_extranetvm2" , $conect);
            $url = "http://localhost/ExtranetVM2/public/";
        } else if ($host=="local2"){
            $conect = mysql_connect("localhost","root","");
            $banco = mysql_select_db("db_extranetvm2" , $conect);
            $url = "http://192.168.1.69/ExtranetVM2/public/";
        } else if ($host=="remoto"){
            $conect=mysql_connect("mysql02.extranetvianaemoura.hospedagemdesites.ws","extranetvianae1","vm3015nm");
            $banco=mysql_select_db("extranetvianae1",$conect);
            $url = "http://extranet.vianaemoura.com.br/";
        }

        if (!$banco) {
            echo "<script>alert('nao conectou')</script>";
            exit();
        } else {
            mysql_set_charset('utf8');
            mysql_query("SET NAMES UTF8");
        }
        
        $GLOBALS['user'] = Adm_PessoaMapper::getInstance()->find(array(array("id","=",$_SESSION['user_id'])));
        
        
//testa validacao
        $novo = $this->validacao($m,$c,$a);
        $nomeController = $novo['m']."_".$novo['c'].'Controller';
        $nomeAction = $novo['a'].'Action';
        
//testa se tem permissao 
        if (!Adm_PermissaoMapper::getInstance()->autorizado($novo['m'],$novo['c'],$novo['a'])) {
            header("Location: ?m=Index");
            exit();
        }
        
//dados da view
        $view = new View($novo['m'],$novo['c'],$novo['a'], $url);
        
//inicia controller
        $this->controller = new $nomeController($view);		
        $this->controller->$nomeAction();
        $this->controller->view->render();

        mysql_close();

    }

    public function validacao($m, $c, $a){
        
        $manutencao = 0;
        
        if ($manutencao == 1 && (!isset($_GET['hack'])) && $a!="Login" && $_SESSION['user_id']!=117) {
            $novo['m'] = "Index";     
            $novo['c'] = "Index";
            $novo['a'] = "Manutencao";
        }
        else {
            //primeiro caso: está acessando alguma pagina livre
            if ($m=="Index" && $c=="Index" && $a!="Home") {
                $novo['m'] = $m;    
                $novo['c'] = $c;
                $novo['a'] = $a;
            } 
            //está acessando alguma bloqueada
            else {
                
                //agora ve se ele está logado
                if ($GLOBALS['user'] instanceof Adm_Pessoa) {
                    
                    //testa se a sessao ainda é valida
                    if ($this->sessaoValida()){
                        
                        //testa se há permissao para o ação em questão
                        if (Adm_PermissaoMapper::getInstance()->autorizado($m,$c,$a)) {
                            $novo = array("m"=>$m,"c"=>$c,"a"=>$a);
                        }
                        else {
                            Util_FlashMessage::write("Acesso Negado");
                            $novo = array("m"=>"Index","c"=>"Index","a"=>"Home");
                        }
                        
                    }
                    else {
                        Util_FlashMessage::write("Sessão expirada");
                        $novo = array("m"=>"Index","c"=>"Index","a"=>"Index");
                    }
                }
                //se não estiver logado redireciona pra index
                else {
                    Util_FlashMessage::write("É necessário fazer login");
                    $novo = array("m"=>"Index","c"=>"Index","a"=>"Index");
                }
            }
        }
        return $novo;
    }

    public function sessaoValida(){

        if (isset($_SESSION['LAST_ACTIVITY'])) {
            $arg['now'] = date("Y-m-d H:i:s", time());
            $arg['stamp'] = date("Y-m-d H:i:s",$_SESSION['LAST_ACTIVITY']);                
            $arg['dif'] = (time() - $_SESSION['LAST_ACTIVITY']);
            $arg['login'] = $_SESSION['login'];
            
            if((time() - $_SESSION['LAST_ACTIVITY'])>1800) { //60*30
                session_unset();
                $retorno = false;
            } else {
                $_SESSION['LAST_ACTIVITY'] = time();
                $retorno = true;
            }
        } else {
            $retorno = false;
        }

        return $retorno;

    }
	
}