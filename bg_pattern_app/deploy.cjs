// deploy.js
const FtpDeploy = require('ftp-deploy');
require('dotenv').config();
const obj = {
  user: process.env.FTP_USER,
  password: process.env.FTP_PASSWORD,
  host: process.env.FTP_HOST,
  port: 21,
  localRoot: "./dist",
  remoteRoot: process.env.FTP_REMOTE_DIR,
  include: ["*", "**/*"]
}
new FtpDeploy().deploy(obj)
.then(() => console.log('✅ Деплой завершен'))
.catch(err => {
  console.log(obj)
  console.error('❌ Ошибка:', err);
  process.exit(1);
});