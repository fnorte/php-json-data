<?php

/**
 *
 * Description : Coleta de dados para o LEIA MAIS em JSON, extendendo da classe absatrata de cache do JSON
 *
 * @package soubh
 **/

require_once 'class-json-data.php';

/**
 * Coleta de dados para o LEIA MAIS em JSON para cache
 */
class leiamais extends Json_Data
{

	public $data, $post_id, $post_type;

	/**
	 * Constructor method
	 * @param integer $post_id
	 * @param string $post_type
	 * @throws \Exception
	 */
	public function __construct($post_id, $post_type = 'post')
	{
		$this->file_name = $post_id . '_leiamais';
		parent::__construct();
		$this->path .= 'leiamais/';
		// Test if path  exists and create if don't (throw an error if is not possible to create)
		if (!file_exists($this->path)) {
			if (!mkdir($this->path)) {
				throw new Exception('Não foi possível criar o diretório ' . $this->path);
			}
		} else {
			if (!is_writable($this->path)) {
				throw new Exception('O diretório ' . $this->path . ' não tem permissão de escrita.');
			}
		}
		$this->data = array(); // DADOS para gerar o JSON.
		$this->post_id   = $post_id;
		$this->post_type = $post_type;
	}

	/**
	 * Generate and validate data
	 **/
	public function save_data()
	{

		$this->get_post_leiamais();
		if ( empty( $this->data ) ) {
			return false;
		}
		$json = json_decode($this->create_json('.json', $this->data), true);

		return $json ;
	}

	/**
	 * Retorna os 10 posts mais recentes que são relacionados ao post atual, 
	 * a relação é de outros posts com uma das mesmas tags
	 * @return mixed array|bool(false)
	 **/
	public function get_post_leiamais()
	{
		if ( 'agenda' !== $this->post_type ) {
			$args = array(
				'post_type' => $this->post_type,
				'post_status' => 'publish',
				'posts_per_page' => 10,
				'post__not_in' => array($this->post_id),
				'tag__in' => wp_get_post_tags($this->post_id, array('fields' => 'ids')),
				'orderby' => 'post_date',
				'order' => 'DESC',
			);

			$list = array();
			$query = new WP_Query($args);
			if ($query->have_posts()) {
				while ($query->have_posts()) {
					$query->the_post();
					$l_id = get_the_ID();
					$list[ $l_id ] = array();
					$list[ $l_id ][ 'url' ] = get_the_permalink();
					$list[ $l_id ][ 'title' ] = get_the_title();
					$list[ $l_id ][ 'img' ] = get_post_thumbnail_id();
			
					$l_id = false;
					
				}
			} 
			wp_reset_postdata();
			$this->data = $list;
		} else {
			return false;
		}

	}
	
	public function get_data() {
		return $this->check_file();
	}
}
