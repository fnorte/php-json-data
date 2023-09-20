<?php
/**
 *
 * Description : Coleta de dados de todos os elementops para a HOME em JSON, extendendo da classe absatrata de cache do JSON
 *
 * @package soubh
 **/

require_once 'class-json-data.php';

/**
 * Description : Coleta de dados para a HOME
 **/
class JD_Home extends Json_Data {
	/**
	 * Nome do arquivo
	 *
	 * @var string
	 **/
	public $file_name = 'json-data-home';
	/**
	 * IDs de Posts para evitar DUPLICAÇÃO.
	 *
	 * @var array
	 **/
	public $do_not_duplicate = array();
	/**
	 * Todos os dados em array
	 *
	 * @var array
	 **/
	public $data = array(); // DADOS para gerar o JSON.

	/**
	 * Geenerate and validate data
	 **/
	public function save_data() {
		
		$this->get_destaques();
		$this->get_ultimas();
		$this->get_oqfazerembh();

		foreach ( $this->data['destaques'] as $dest_id => $destaque ) {
			if ( 'Hardnews' === $dest_id ) {
				$c = 0;
				if ( isset( $destaque['posts'] )){
					$count = count( $destaque['posts'] );
					for ( $i = $count ; $i < 4; $i++ ) {
						if ( isset( $this->data['ultimas'][ $c ] ) ) {
							$this->data['destaques'][ $dest_id ]['posts'][ $i ] = $this->data['ultimas'][ $c ];
							unset( $this->data['ultimas'][ $c ] );
						}
						$c++;
					} 
				}
			}
		}
		return json_decode ( $this->create_json( '.json', $this->data ), true );
		
	}

	/**
	 * Query dos Destaques
	 **/
	public function get_destaques() {
		$cp = 0;
		
		$post_in = array();
		
		$destaques = new WP_Query(
			array(
				'no_found_rows'       => true,
				'post_type'           => 'destaques',
				'posts_per_page'      => '-1',
				'ignore_sticky_posts' => 1,
				)
		);

		$this->data['destaques'] = array();

		$destaque_posts = array();
		if ( $destaques->have_posts() ) {
			while ( $destaques->have_posts() ) {
				$destaques->the_post();
				
				$destaq_title = get_the_title();
				
				$this->data['destaques'][ $destaq_title ]          = array();
				$this->data['destaques'][ $destaq_title ]['id']    = get_the_id();

				$postagens = get_fields();
				$post_in   = array();
				
				if ( isset( $postagens['destaques'] ) ) {
					$post_in = array();
					$pic = 0;
					
					foreach ( $postagens['destaques'] as $post_value ) {
						
						$data_entrada = date( 'Y-m-d H:i:s', strtotime($post_value['data_entrada']) - ( 3 * 60 * 60 ) );
						$data_saida = false;
						
						if ( ! empty( $post_value['data_saida'] ) ) {
							$data_saida = date( 'Y-m-d H:i:s', strtotime($post_value['data_saida']) - ( 3 * 60 * 60 ) );
						}
						
						if ( 
							! in_array( $post_value['post'], $this->do_not_duplicate ) &&
							time() > strtotime( $data_entrada ) &&
							( empty( $data_saida ) || time() < strtotime( $data_saida ) )
						) {
							$post_in[$pic] = array();
							$post_in[$pic]['post'] = $post_value['post'];
							$post_in[$pic]['data_entrada'] = $data_entrada;
							$post_in[$pic]['data_saida']   = $data_saida;
							$pic++;
						}
					}
					$destaque_posts[ $destaq_title ] = $post_in;
				}
			}
		}
		wp_reset_postdata();
		
		if ( ! empty( $destaque_posts ) ) {
			foreach ( $destaque_posts as $destaq_title => $arr_posts ) {
				foreach( $arr_posts as $post_data ) {
					$post_type  = get_post_type( $post_data['post'] ); 
					$dest_posts = new WP_Query(
						array(
							'no_found_rows' => true,
							'post_type'     => $post_type,
							'p'             => $post_data['post'],
						)
					);
					$arr_data = $this->get_query_data($dest_posts, true, true);
					if (! empty( $arr_data ) ) {
						$arr_data['data_entrada'] = $post_data['data_entrada'];
						$arr_data['data_saida']   = $post_data['data_saida'];
						
						$this->data['destaques'][ $destaq_title ]['posts'][] = $arr_data;
					}
					wp_reset_postdata();
					
				}
			}
		}
	}


	/**
	 * Query das Últimas noticias
	 **/
	public function get_ultimas($qtd = 10) {

		$this->data['ultimas'] = array();

		$qr_posts = new WP_Query( array(
		  'no_found_rows'  => true,
		  'post_type'      => 'post',
		  'post_status'    => 'publish',
		  'posts_per_page' => $qtd,
		  'post__not_in'  => $this->do_not_duplicate,
		  #'nopaging'       => true,
		  'orderby'        => 'date',
		  'order'          => 'DESC'
		) ); 
		
		$this->data['ultimas'] = $this->get_query_data( $qr_posts );
		wp_reset_postdata();
	}

	/**
	 * Query do cpt O Que Fazer em BH
	 **/
	public function get_oqfazerembh($qtd = 10) {

		$this->data['oqfazerembh'] = array();

		$qr_posts = new WP_Query( array(
		  'no_found_rows'  => true,
		  'post_type'      => 'o_que_fazer_em_bh',
		  'post_status'    => 'publish',
		  'posts_per_page' => $qtd,
		  'post__not_in'  => $this->do_not_duplicate,
		  #'nopaging'       => true,
		  'orderby'        => 'date',
		  'order'          => 'DESC'
		) ); 
		
		$this->data['oqfazerembh'] = $this->get_query_data( $qr_posts );
		wp_reset_postdata();
	}



	/**
	 * Query do cpt Agenda de Eventos
	 **/
	public function get_agenda() {

		$this->data['agenda'] = array();

		$hj = date('Y-m-d');
		$dd = date('Y-m-d', strtotime('+6 days'));
		
		$qr_posts = new WP_Query( array(
		  'no_found_rows'  => true,
		  'nopaging'       => true,
		  'post_type'      => 'agenda',
		  'post_status'    => 'publish',
		  'meta_query'     => array(
		  		'relation' => 'AND',
		  		array(
		  			'key'     => 'data_fim',
		  			'value'   => $hj,
		  			'compare' => '>=',
		  			'type'    => 'DATE',
		  		),
		  		array(
		  			'key'     => 'data_ini',
		  			'value'   => $dd,
		  			'compare' => '<=',
		  			'type'    => 'DATE',
		  		),

		  	),
		  'orderby'        => 'date',
		  'order'          => 'DESC'
		) ); 

		$this->data['agenda'] = $this->get_query_data( $qr_posts );

		foreach ( $this->data['agenda'] as $id => $agenda ) {
			$acf = get_fields($agenda['id']);
			$this->data['agenda'][$id]['acf'] = $acf;
		}

		wp_reset_postdata();
	}




	protected function get_query_data( $query, $dupl = true, $onlyone = false ) {
		
		$c = 0;

		$array = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$tmp_array = [];

				$tmp_array['id']  = get_the_ID();
				if ( $dupl === true ) { 
					$this->do_not_duplicate[] = $tmp_array['id'];
				}
				$tmp_array['url'] = get_the_permalink();

				if ( ( function_exists( 'has_post_thumbnail' ) ) && ( has_post_thumbnail() ) ) {
					$tmp_array['thumb']   = get_post_thumbnail_id();
				}

				if ( get_post_type() == 'agenda' ) {
					$category = get_the_terms( get_the_ID(), 'categoria_eventos' );
				} else {
					$category = get_the_terms( get_the_ID(), 'category' );
				}
				if (isset( $category[0]->name )) {
					$tmp_array['category'] = $category[0]->name;
				} else {
					$tmp_array['category'] = ' ';
				}
				$tmp_array['date']     = get_the_time( 'U' );
				$tmp_array['title']    = get_the_title();
				$tmp_array['excerpt']  = implode(' ', array_slice(explode(' ', get_the_excerpt()), 0, 30)).' (...)';

				$tmp_acf = get_fields();
				$tmp_array['patrocinado'] = false;
				if ( isset( $tmp_acf['patrocinado'] ) && $tmp_acf['patrocinado'] ) {
					$tmp_array['patrocinado'] = true;
				}
				unset($tmp_acf);

				if ( ! $onlyone ) {
					$array[ $c ] = $tmp_array;
				} else {
					$array = $tmp_array;
				}

				$c++;
			}
		}
		wp_reset_postdata();

		return $array;

	}

}

