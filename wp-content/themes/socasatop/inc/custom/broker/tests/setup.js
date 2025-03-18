// Importar as extensões do Jest para o DOM
require('@testing-library/jest-dom');

// Mock do SweetAlert2
global.Swal = {
    fire: jest.fn(),
    close: jest.fn()
};

// Mock do jQuery
global.$ = global.jQuery = jest.fn((selector) => ({
    on: jest.fn(),
    off: jest.fn(),
    show: jest.fn(),
    hide: jest.fn(),
    html: jest.fn(),
    val: jest.fn(),
    addClass: jest.fn(),
    removeClass: jest.fn(),
    closest: jest.fn(),
    remove: jest.fn(),
    append: jest.fn(),
    find: jest.fn().mockReturnThis(),
    data: jest.fn(),
    trigger: jest.fn()
}));

// Mock do objeto ajaxurl do WordPress
global.ajaxurl = 'http://localhost/wp-admin/admin-ajax.php';

// Mock do objeto fetch
global.fetch = jest.fn();

// Mock do objeto console
global.console = {
    ...console,
    error: jest.fn(),
    log: jest.fn(),
    warn: jest.fn()
}; 