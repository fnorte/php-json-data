<?php
/**
 *
 * Description : Geração em cache do arquivo JSON de dados, classe abstrata para ser usado aonde se captura os dados.
 *
 * @package soubh
 **/

/**
 * Description: Captura de dados da home page em JSON para acelerar a geração da  HOME PAGE
 * esses dados serão relativos apenas a FEATURE inicial e os WIDGETS de DESTAQUES
 *
 * @var integer  Tempo de reconstrução.
 **/
abstract class Json_Data {
	public $file_name, $path, $time_check;

	public function __construct( $time = false ) {
		if ( ! isset( $this->file_name ) ) {
			throw new LogicException( get_class( $this ) . ' must have a $file_name' );
		}

		$this->path = ABSPATH . 'wp-content/cache/'; // Path para armazenar o JSON.
		if ( ! is_dir( $this->path ) ) {
			mkdir( $this->path, 0775 );
		}
		if ( false !== $time ) { // Caso receba um tempo diferente do padrão.
			$this->time_check = ( $time * 60 );
		} else {
			//$this->time_check = 5*60; // Padrão 5 minutos.
			$this->time_check = 5; // DEvelop time 5 secs.
		}
	}

	public function check_file() {

		if ( file_exists( $this->path . $this->file_name . '.json' ) ) { // Arquivo PRINCIPAL.
			return $this->read_file( '.json' );
		} elseif ( file_exists( $this->path . $this->file_name . '.old' ) ) { // Arquivo BACKUP pra não segurar a fila.
			return $this->read_file( '.old' );
		} else { // Ninguém existe.
			return $this->save_data();
		}
	}

	public function read_file( $ext = false ) {
		
		if ( ! $ext ) $ext = '.json';

		// Falta teste
		if ( file_exists( $this->path . $this->file_name . $ext  ) ) {
			$json  = file_get_contents( $this->path . $this->file_name . $ext );
			$dados = json_decode( $json, true );
		} else {
			$j     = fopen( $this->path . $this->file_name . $ext , 'w' );
			$dados = $this->save_data();
			fwrite( $j, json_encode( $dados , true ) );
		}
		
		// Checagem da data do arquivo.
		if ( $ext === '.json' && ! $this->validate_file( $ext ) ) {
			// Arquivo já está velho.
			$this->create_json( '.old', $dados );
			unlink( $this->path . $this->file_name . '.json' );
		}
		return $dados;

	}

	public function validate_file( $ext ) {
		$data_mod = filectime( $this->path . $this->file_name . $ext );
		$now      = gmdate( time() );
		$dif      = $now - $data_mod;
		return $dif >= $this->time_check ? false : true;
	}

	public function create_json( $ext, $data  ) {
		$json_data = wp_json_encode( $data );
		$json_err  = json_last_error_msg();
		
		if ( 'No error' !== $json_err ) {
			echo $json_err . "\n";
			return false;
		}

		$f = fopen($this->path . $this->file_name . $ext, 'w');
		
		if (!$f) {
			return false;
		}

		$save = fwrite($f, $json_data);
		if ($ext === '.json' && file_exists($this->path . $this->file_name . '.old')) {
			unlink($this->path . $this->file_name . '.old');
		}

		fclose($f);
		
		return false !== $save ? $json_data : false;
	}

	abstract public function save_data();

	public function get_json_data($file = false) {
		if ( ! $file ) {
			if ( isset( $this->file_name ) ) {
				$file = $this->file_name;
			} else {
				return false;
			}
		} else {
			$this->file_name = $file;
		}

		return $this->read_file();
	}
}































