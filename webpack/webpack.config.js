const path = require('path');

module.exports = {
    mode: 'production',
    entry: { 
        atend: './src/atendimentos_api.js',
        term: './src/terminais_api.js',
        user: './src/usuarios_api.js',
        prod: './src/produtos_api.js',
        login: './src/login.js',
    },
    output: {
        filename: '[name].bundle.js',
        path: path.resolve(__dirname, 'dist/js'),
    },
    resolve: {
        alias: {
            // O build ESM de libsodium-wrappers importa "./libsodium.mjs" via
            // caminho relativo que não existe dentro do próprio pacote (só
            // existe em node_modules/libsodium) - força o build CommonJS via
            // caminho de arquivo absoluto (não pelo specifier do pacote, que
            // esbarraria no "exports" do package.json), que resolve
            // "libsodium" normalmente via node_modules.
            'libsodium-wrappers$': path.resolve(__dirname, 'node_modules/libsodium-wrappers/dist/modules/libsodium-wrappers.js'),
        },
    },
    devServer: {
        static: {
          directory: path.resolve(__dirname, 'dist'),
        },
        port: 3000,
        open: true,
        hot: true,
        compress: true,
        historyApiFallback: true,
      },
      module: {
        rules: [
          {
            test: /\.css$/i,
            include: path.resolve(__dirname, 'src'),
            use: ['style-loader', 'css-loader', 'postcss-loader'],
          },
        ],
      },
};