const { execSync } = require('child_process')

execSync('reuse addheader --license "AGPL-3.0-or-later" --copyright "Agora citizen" src/**/*.{vue,js,ts,scss}', { stdio: 'inherit' })

execSync('reuse addheader --license "AGPL-3.0-or-later" --copyrigh "Agora citizen" dist/**/*.{js,css}', { stdio: 'inherit' })

