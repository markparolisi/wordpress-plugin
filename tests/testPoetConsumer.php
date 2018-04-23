<?php

class PoetConsumerTest extends WP_UnitTestCase {


	function setUp() {

		$p                    = $this->factory->post->create( [ 'post_title' => 'Test Post' ] );
		$this->object->author = 'Jane Doe';
		$this->object->url    = 'https://api.frost.po.et/works';
		$this->object->token  = 'xxxxxxxxxxx';
		$this->object->post   = [
			'ID'           => $p->ID,
			'post_title'   => $p->post_title,
			'post_content' => $p->post_content,
		];

		$this->consumer = new \Poet\Consumer( $this->object->author, $this->object->url, $this->object->token, $this->object->post );
	}

	function test_constructor() {

		$this->assertEquals( $this->object->author, $this->consumer->author, 'Author properties should match via constructor' );
		$this->assertEquals( $this->object->url, $this->consumer->url, 'Author properties should match via constructor' );
		$this->assertEquals( $this->object->token, $this->consumer->token, 'Author properties should match via constructor' );
		$this->assertEquals( $this->object->post, $this->consumer->post, 'Author properties should match via constructor' );

	}
}
