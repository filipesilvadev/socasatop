<?php
/**
 * Testes unitários para funções de login
 */
class TestLoginFunctions extends WP_UnitTestCase {
    
    public function setUp() {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }
    
    public function test_custom_register_url() {
        // Criar página de teste
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
            'post_name' => 'cadastro-corretor'
        ]);
        
        $url = custom_register_url('default-url');
        $this->assertStringContainsString('cadastro-corretor', $url);
        
        wp_delete_post($page_id, true);
    }
    
    public function test_custom_lostpassword_url() {
        // Criar página de teste
        $page_id = $this->factory->post->create([
            'post_type' => 'page',
            'post_name' => 'recuperar-senha'
        ]);
        
        $url = custom_lostpassword_url('default-url', 'http://redirect.test');
        $this->assertStringContainsString('recuperar-senha', $url);
        $this->assertStringContainsString('redirect_to=', $url);
        
        wp_delete_post($page_id, true);
    }
    
    public function test_limit_login_attempts() {
        for ($i = 0; $i < 6; $i++) {
            $result = limit_login_attempts(
                new WP_Error(),
                'test_user',
                'wrong_pass'
            );
            
            if ($i < 5) {
                $this->assertInstanceOf(WP_Error::class, $result);
            } else {
                $this->assertEquals(
                    'too_many_attempts',
                    $result->get_error_code()
                );
            }
        }
        
        // Limpar transient
        delete_transient('login_attempts_127.0.0.1');
    }
    
    public function test_custom_login_form_classes() {
        $classes = custom_login_form_classes([]);
        $this->assertContains('login-form', $classes);
        $this->assertContains('needs-validation', $classes);
    }
    
    public function test_custom_login_form_fields() {
        $fields = [
            'user_login' => '<input type="text" name="log" />',
            'user_pass' => '<input type="password" name="pwd" />'
        ];
        
        $result = custom_login_form_fields($fields);
        
        $this->assertStringContainsString('type="email"', $result['user_login']);
        $this->assertStringContainsString('required', $result['user_login']);
        $this->assertStringContainsString('placeholder', $result['user_pass']);
    }
    
    public function test_notify_suspicious_login() {
        // Simular horário suspeito
        $mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['current_time'])
            ->getMock();
            
        $mock->method('current_time')
            ->willReturn('03:00:00');
            
        add_filter('current_time', [$mock, 'current_time']);
        
        // Capturar email
        $emails = [];
        add_action('wp_mail', function($args) use (&$emails) {
            $emails[] = $args;
        });
        
        notify_suspicious_login('test_user', new WP_User(1));
        
        $this->assertCount(1, $emails);
        $this->assertStringContainsString('Login Suspeito', $emails[0]['subject']);
        
        remove_filter('current_time', [$mock, 'current_time']);
    }
} 