const express = require('express');
const cors = require('cors');
const multer = require('multer');
const path = require('path');
const { spawn } = require('child_process');
const fs = require('fs').promises;
const { OpenAI } = require('openai');
require('dotenv').config();

const app = express();
const port = 5000;

// Configurar OpenAI
const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

// Habilitar CORS para localhost:3000 y localhost (Apache)
app.use(cors({ origin: ['http://localhost:3000', 'http://localhost'] }));
app.use(express.json());

// Configurar almacenamiento de archivos
const storage = multer.diskStorage({
  destination: './uploads/',
  filename: (req, file, cb) => {
    cb(null, `audio-${Date.now()}.wav`);
  },
});
const upload = multer({ storage });

// Crear carpeta para audios
(async () => {
  try {
    await fs.mkdir('./uploads', { recursive: true });
  } catch (error) {
    console.error('Error creating uploads folder:', error);
  }
})();

// Ruta para transcribir audio
app.post('/transcribe', upload.single('audio'), async (req, res) => {
  try {
    const audioPath = req.file.path;
    const pythonPath = path.join(__dirname, 'venv', 'Scripts', 'python.exe');
    const scriptPath = path.join(__dirname, 'transcribe.py');

    const pythonProcess = spawn(pythonPath, [scriptPath, audioPath], {
      encoding: 'utf8',
      maxBuffer: 1024 * 1024,
      shell: true
    });

    let transcription = '';

    pythonProcess.stdout.on('data', (data) => {
      transcription += data.toString('utf8');
      console.log('Raw data:', data.toString('utf8'));
    });

    pythonProcess.stderr.on('data', (data) => {
      console.error('Python error:', data.toString('utf8'));
    });

    pythonProcess.on('close', (code) => {
      if (code !== 0) {
        console.error(`Python process exited with code ${code}`);
        res.status(500).json({ error: 'Transcription failed' });
        return;
      }
      transcription = transcription.trim();
      console.log('Processed transcription:', transcription);
      res.json({ transcription });
      fs.unlink(audioPath).catch(err => console.error('Error deleting file:', err));
    });
  } catch (error) {
    console.error('Error processing audio:', error);
    res.status(500).json({ error: 'Server error' });
  }
});

// Ruta para generar resumen
app.post('/summarize', async (req, res) => {
  try {
    const { transcription } = req.body;
    if (!transcription) {
      return res.status(400).json({ error: 'Transcription is required' });
    }

    const response = await openai.chat.completions.create({
      model: 'gpt-4o-mini',
      messages: [
        {
          role: 'system',
          content: 'You are an assistant that generates concise summaries in Spanish. Summarize the input text in 3-5 sentences, formatted as a report, capturing the main ideas and key points.'
        },
        {
          role: 'user',
          content: `Resumir el siguiente texto en 3-5 frases como un reporte: ${transcription}`
        }
      ],
      temperature: 0.5,
      max_tokens: 150,
    });

    const summary = response.choices[0].message.content;
    res.json({ summary });
  } catch (error) {
    console.error('Error generating summary:', error);
    res.status(500).json({ error: 'Summary generation failed' });
  }
});

// Iniciar servidor
app.listen(port, () => {
  console.log(`Server running on http://localhost:${port}`);
});