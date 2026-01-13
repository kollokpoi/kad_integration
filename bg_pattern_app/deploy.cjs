const FtpDeploy = require('ftp-deploy');
const ftp = require('basic-ftp');
require('dotenv').config();

async function deploy() {
  console.log('🚀 Начинаю деплой...');
  
  const config = {
    user: process.env.FTP_USER,
    password: process.env.FTP_PASSWORD,
    host: process.env.FTP_HOST,
    port: 21,
    localRoot: "./dist",
    remoteRoot: process.env.FTP_REMOTE_DIR,
    include: ["*", "**/*"],
    deleteRemote: false, // не удалять старые файлы
    forcePasv: true, // важно для некоторых хостингов
  };

  console.log('📁 Проверяю локальную папку dist...');
  
  const fs = require('fs');
  const path = require('path');
  
  if (!fs.existsSync('./dist')) {
    console.error('❌ Папка dist не существует! Сначала запустите npm run build');
    process.exit(1);
  }

  console.log('📦 Содержимое папки dist:');
  const files = fs.readdirSync('./dist');
  files.forEach(file => {
    const filePath = path.join('./dist', file);
    const stat = fs.statSync(filePath);
    console.log(`  ${file} (${stat.isDirectory() ? 'папка' : 'файл'})`);
  });

  try {
    // Сначала проверим подключение и создадим директории
    console.log('🔗 Проверяю подключение к FTP...');
    await testFTPConnection();
    
    console.log('📤 Начинаю загрузку файлов...');
    const ftpDeploy = new FtpDeploy();
    
    ftpDeploy.on('uploading', function(data) {
      console.log(`  📤 ${data.filename} (${data.transferredFileCount}/${data.totalFileCount})`);
    });
    
    ftpDeploy.on('uploaded', function(data) {
      console.log(`✅ Загружено ${data.transferredFileCount} из ${data.totalFileCount} файлов`);
    });
    
    ftpDeploy.on('log', function(data) {
      console.log('📝 FTP:', data);
    });
    
    await ftpDeploy.deploy(config);
    console.log('✅ Деплой успешно завершен!');
    
  } catch (err) {
    console.error('❌ Ошибка при деплое:', err.message);
    
    // Попробуем альтернативный способ
    console.log('🔄 Пробую альтернативный метод загрузки...');
    await uploadManually();
  }
}

async function testFTPConnection() {
  const client = new ftp.Client();
  client.ftp.verbose = true;
  
  try {
    await client.access({
      host: process.env.FTP_HOST,
      user: process.env.FTP_USER,
      password: process.env.FTP_PASSWORD,
      port: 21,
      secure: false,
    });
    
    console.log('✅ Подключение к FTP успешно');
    
    // Проверяем удаленную директорию
    const remoteDir = process.env.FTP_REMOTE_DIR;
    console.log(`📁 Проверяю удаленную директорию: ${remoteDir}`);
    
    try {
      await client.cd(remoteDir);
      console.log('✅ Удаленная директория существует');
    } catch (e) {
      console.log('⚠️  Директория не существует, пробую создать...');
      
      // Создаем директории по частям
      const parts = remoteDir.split('/').filter(p => p);
      let currentPath = '';
      
      for (const part of parts) {
        currentPath += '/' + part;
        try {
          await client.cd(currentPath);
        } catch {
          await client.mkdir(currentPath);
          await client.cd(currentPath);
          console.log(`  📁 Создана папка: ${currentPath}`);
        }
      }
    }
    
    // Показываем содержимое удаленной директории
    const list = await client.list();
    console.log('📁 Содержимое удаленной директории:');
    list.forEach(item => {
      console.log(`  ${item.name} (${item.isDirectory ? 'папка' : 'файл'})`);
    });
    
    await client.close();
    
  } catch (err) {
    await client.close();
    throw err;
  }
}

async function uploadManually() {
  const client = new ftp.Client();
  client.ftp.verbose = true;
  
  try {
    await client.access({
      host: process.env.FTP_HOST,
      user: process.env.FTP_USER,
      password: process.env.FTP_PASSWORD,
      port: 21,
      secure: false,
    });
    
    // Переходим в корень
    await client.cd('/');
    
    // Создаем целевую директорию
    const remoteDir = process.env.FTP_REMOTE_DIR;
    const parts = remoteDir.split('/').filter(p => p);
    
    for (const part of parts) {
      try {
        await client.cd(part);
      } catch {
        await client.mkdir(part);
        await client.cd(part);
        console.log(`📁 Создана папка: ${part}`);
      }
    }
    
    // Загружаем файлы из dist рекурсивно
    console.log('📤 Загружаю файлы вручную...');
    await uploadDirectory('./dist', client);
    
    console.log('✅ Ручная загрузка завершена!');
    
  } catch (err) {
    console.error('❌ Ошибка при ручной загрузке:', err.message);
  } finally {
    client.close();
  }
}

async function uploadDirectory(localPath, client) {
  const fs = require('fs');
  const path = require('path');
  
  const items = fs.readdirSync(localPath);
  
  for (const item of items) {
    const localItemPath = path.join(localPath, item);
    const stats = fs.statSync(localItemPath);
    
    if (stats.isDirectory()) {
      try {
        await client.cd(item);
      } catch {
        await client.mkdir(item);
        await client.cd(item);
      }
      await uploadDirectory(localItemPath, client);
      await client.cd('..');
    } else {
      console.log(`  📄 Загружаю: ${item}`);
      await client.uploadFrom(localItemPath, item);
    }
  }
}

// Запускаем деплой
deploy();