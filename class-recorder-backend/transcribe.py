import sys
import argparse
import whisper
import codecs

# Forzar codificación UTF-8 en la salida
sys.stdout.reconfigure(encoding='utf-8')

def transcribe_audio(audio_path):
    # Cargar el modelo Whisper
    model = whisper.load_model("base")
    
    # Transcribir el audio con lenguaje español
    result = model.transcribe(audio_path, language="es")
    transcription = result["text"]
    
    # Asegurar codificación UTF-8
    transcription = transcription.encode('utf-8', errors='replace').decode('utf-8', errors='replace')
    
    # Depuración: Mostrar la transcripción cruda
    print("Debug: Raw transcription:", transcription, file=sys.stderr)
    
    return transcription

def main():
    # Argumentos de la línea de comandos
    parser = argparse.ArgumentParser(description="Transcribe audio using Whisper")
    parser.add_argument("audio_path", type=str, help="Path to the audio file")
    args = parser.parse_args()
    
    # Transcribir el audio
    transcription = transcribe_audio(args.audio_path)
    
    # Guardar en archivo temporal para depuración
    with codecs.open('transcription_debug.txt', 'w', encoding='utf-8') as f:
        f.write(transcription)
    
    # Imprimir la transcripción
    print(transcription)

if __name__ == "__main__":
    main()