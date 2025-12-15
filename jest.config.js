module.exports = {
    testEnvironment: 'jsdom',
    testMatch: ['**/tests/javascript/**/*.test.js'],
    collectCoverage: true,
    coverageDirectory: './tests/coverage-js',
    coverageReporters: ['text', 'html', 'lcov'],
    collectCoverageFrom: [
        'js/**/*.js',
        '!js/**/*.min.js',
        '!js/vendor/**',
    ],
    coverageThreshold: {
        global: {
            branches: 70,
            functions: 70,
            lines: 80,
            statements: 80,
        },
    },
    setupFilesAfterEnv: ['<rootDir>/tests/javascript/setup.js'],
    moduleNameMapper: {
        '\\.(css|less|scss|sass)$': '<rootDir>/tests/javascript/__mocks__/styleMock.js',
    },
};
