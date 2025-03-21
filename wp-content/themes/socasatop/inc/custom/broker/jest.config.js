module.exports = {
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: ['./tests/setup.js'],
    moduleNameMapper: {
        '\\.(css|less|scss|sass)$': 'identity-obj-proxy'
    },
    testMatch: [
        '**/tests/**/*.test.js'
    ],
    transform: {
        '^.+\\.js$': 'babel-jest'
    }
}; 