import React, { useState, useRef, useEffect } from 'react';
import axios from 'axios';
import './App.css';

function App() {
  // Estados
  const [userRole, setUserRole] = useState(null);
  const [loading, setLoading] = useState(true);
  const [recording, setRecording] = useState(false);
  const [audioURL, setAudioURL] = useState('');
  const [transcription, setTranscription] = useState('');
  const [summary, setSummary] = useState('');
  const [savedTranscriptions, setSavedTranscriptions] = useState([]);
  const [editingId, setEditingId] = useState(null);
  const [editingTitle, setEditingTitle] = useState('');
  const [students, setStudents] = useState([]);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [studentTranscriptions, setStudentTranscriptions] = useState([]);
  const [editingStudentId, setEditingStudentId] = useState(null);
  const [editingField, setEditingField] = useState(null);
  const [editingValue, setEditingValue] = useState('');
  
  const mediaRecorderRef = useRef(null);
  const audioChunksRef = useRef([]);

  // Cargar rol y datos iniciales
  useEffect(() => {
    const fetchUserRole = async () => {
      try {
        const res = await axios.get('./get_user_role.php', { withCredentials: true });
        if (res.data && res.data.success) {
          setUserRole(res.data.role);
          
          if (res.data.role === 'alumno') {
            const transcRes = await axios.get('./get_transcriptions.php', { withCredentials: true });
            if (transcRes.data && transcRes.data.success) {
              setSavedTranscriptions(transcRes.data.records);
            }
          } else if (res.data.role === 'docente') {
            const studRes = await axios.get('./get_students.php', { withCredentials: true });
            if (studRes.data && studRes.data.success) {
              setStudents(studRes.data.students);
            }
          }
        }
      } catch (err) {
        console.error('Error al cargar datos:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchUserRole();
  }, []);

  const handleSelectStudent = async (student) => {
    setSelectedStudent(student);
    try {
      const res = await axios.post('./get_student_transcriptions.php', { student_id: student.id }, { withCredentials: true });
      if (res.data && res.data.success) {
        setStudentTranscriptions(res.data.records);
      }
    } catch (err) {
      console.error('Error al cargar transcripciones:', err);
      alert('Error al cargar las transcripciones del alumno');
    }
  };

  const startRecording = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaRecorderRef.current = new MediaRecorder(stream);
      audioChunksRef.current = [];

      mediaRecorderRef.current.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunksRef.current.push(event.data);
        }
      };

      mediaRecorderRef.current.onstop = () => {
        const audioBlob = new Blob(audioChunksRef.current, { type: 'audio/wav' });
        const audioUrl = URL.createObjectURL(audioBlob);
        setAudioURL(audioUrl);
        stream.getTracks().forEach(track => track.stop());
        sendAudioToBackend(audioBlob);
      };

      mediaRecorderRef.current.start();
      setRecording(true);
    } catch (error) {
      console.error('Error al iniciar grabación:', error);
      alert('No se pudo iniciar la grabación. Verifica los permisos del micrófono.');
    }
  };

  const stopRecording = () => {
    if (mediaRecorderRef.current && mediaRecorderRef.current.state !== 'inactive') {
      mediaRecorderRef.current.stop();
      setRecording(false);
    } else {
      alert('No hay grabación activa para detener.');
    }
  };

  const handleLogout = async () => {
    if (!window.confirm('¿Deseas cerrar sesión?')) return;
    try {
      await axios.post('./logout.php', {}, { withCredentials: true });
      window.location.href = './index.php';
    } catch (err) {
      window.location.href = './index.php';
    }
  };

  // Enviar audio al backend Node.js para transcribir
  const sendAudioToBackend = async (audioBlob) => {
    const formData = new FormData();
    formData.append('audio', audioBlob, 'recording.wav');
    try {
      const transcribeResponse = await axios.post('http://localhost:5000/transcribe', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      const transcription = transcribeResponse.data.transcription;
      setTranscription(transcription);

      const summaryResponse = await axios.post('http://localhost:5000/summarize', { transcription }, {
        headers: { 'Content-Type': 'application/json' },
      });
      const summaryText = summaryResponse.data.summary;
      setSummary(summaryText);

      // Guardar en base de datos
      try {
        const saveResponse = await axios.post('./save_transcription.php', {
          transcription,
          summary: summaryText
        }, {
          headers: { 'Content-Type': 'application/json' },
          withCredentials: true
        });

        if (saveResponse.data && saveResponse.data.success) {
          setSavedTranscriptions(prev => [saveResponse.data.record, ...prev]);
        }
      } catch (err) {
        console.error('Error al guardar:', err);
      }
    } catch (error) {
      console.error('Error al procesar audio:', error);
      alert('Error al transcribir o resumir el audio. Intenta de nuevo.');
    }
  };

  // Editar transcripción de alumno (docente)
  const handleEditStudentTranscription = async (transcriptionId, field) => {
    try {
      const res = await axios.post('./edit_student_transcription.php', 
        { id: transcriptionId, field: field, value: editingValue }, 
        { withCredentials: true }
      );
      if (res.data && res.data.success) {
        setStudentTranscriptions(prev => prev.map(t => t.id === transcriptionId ? res.data.record : t));
        setEditingStudentId(null);
        setEditingField(null);
        setEditingValue('');
      }
    } catch (err) {
      console.error('Error al editar:', err);
      alert('Error al editar la transcripción');
    }
  };

  if (loading) {
    return <div className="App"><p>Cargando...</p></div>;
  }

  // Panel de docente
  if (userRole === 'docente') {
    return (
      <div className="App">
        <header className="App-header">
          <div className="header-top">
            <div>
              <h1>NoteEd - Panel Docente</h1>
              <p>Gestiona las transcripciones de tus alumnos.</p>
            </div>
            <button className="btn-logout" onClick={handleLogout}>
              Cerrar Sesión
            </button>
          </div>

          <div className="columns">
            <div className="left-column card">
              <h3>Alumnos</h3>
              {students.length === 0 ? (
                <p className="placeholder">No hay alumnos registrados.</p>
              ) : (
                <div className="students-list">
                  {students.map(student => (
                    <button
                      key={student.id}
                      className={`student-item ${selectedStudent?.id === student.id ? 'active' : ''}`}
                      onClick={() => handleSelectStudent(student)}
                    >
                      <strong>{student.first_name} {student.last_name}</strong>
                      <span className="student-username">{student.username}</span>
                    </button>
                  ))}
                </div>
              )}
            </div>

            <div className="right-column card">
              {selectedStudent ? (
                <>
                  <h3>Transcripciones de {selectedStudent.first_name}</h3>
                  {studentTranscriptions.length === 0 ? (
                    <p className="placeholder">Este alumno no tiene transcripciones.</p>
                  ) : (
                    <div className="cards-list">
                      {studentTranscriptions.map(item => (
                        <div key={item.id} className="transcription-card">
                          <div className="card-header">
                            {editingStudentId === item.id && editingField === 'title' ? (
                              <input
                                className="title-input"
                                value={editingValue}
                                onChange={(e) => setEditingValue(e.target.value)}
                                onKeyDown={(e) => {
                                  if (e.key === 'Enter') {
                                    handleEditStudentTranscription(item.id, 'title');
                                  } else if (e.key === 'Escape') {
                                    setEditingStudentId(null);
                                    setEditingField(null);
                                  }
                                }}
                                autoFocus
                              />
                            ) : (
                              <span onClick={() => {
                                setEditingStudentId(item.id);
                                setEditingField('title');
                                setEditingValue(item.title || '');
                              }} style={{ cursor: 'pointer' }}>
                                {item.title && item.title.trim() !== '' ? item.title : new Date(item.created_at).toLocaleString()}
                              </span>
                            )}
                          </div>
                          <div className="card-body">
                            <strong>Transcripción:</strong>
                            {editingStudentId === item.id && editingField === 'transcript' ? (
                              <textarea
                                className="edit-textarea"
                                value={editingValue}
                                onChange={(e) => setEditingValue(e.target.value)}
                                onBlur={() => handleEditStudentTranscription(item.id, 'transcript')}
                              />
                            ) : (
                              <div className="small-text" onClick={() => {
                                setEditingStudentId(item.id);
                                setEditingField('transcript');
                                setEditingValue(item.transcript || '');
                              }} style={{ cursor: 'pointer' }}>
                                {item.transcript}
                              </div>
                            )}
                            <strong style={{ marginTop: '10px', display: 'block' }}>Resumen:</strong>
                            {editingStudentId === item.id && editingField === 'summary' ? (
                              <textarea
                                className="edit-textarea"
                                value={editingValue}
                                onChange={(e) => setEditingValue(e.target.value)}
                                onBlur={() => handleEditStudentTranscription(item.id, 'summary')}
                              />
                            ) : (
                              <div className="small-text" onClick={() => {
                                setEditingStudentId(item.id);
                                setEditingField('summary');
                                setEditingValue(item.summary || '');
                              }} style={{ cursor: 'pointer' }}>
                                {item.summary}
                              </div>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </>
              ) : (
                <p className="placeholder">Selecciona un alumno para ver sus transcripciones.</p>
              )}
            </div>
          </div>
        </header>
      </div>
    );
  }

  // Panel de alumno
  return (
    <div className="App">
      <header className="App-header">
        <div className="header-top">
          <div>
            <h1>NoteEd</h1>
            <p>Graba tus clases y genera resúmenes automáticos.</p>
          </div>
          <button className="btn-logout" onClick={handleLogout}>
            Cerrar Sesión
          </button>
        </div>

        <div className="columns">
          <div className="left-column card">
            <button
              onClick={recording ? stopRecording : startRecording}
              className="record-button"
            >
              {recording ? 'Detener Grabación' : 'Iniciar Grabación'}
            </button>

            {audioURL && (
              <div className="audio-section">
                <h3>Audio Grabado:</h3>
                <audio controls src={audioURL}></audio>
              </div>
            )}

            <h3>Tus transcripciones</h3>
            {savedTranscriptions.length === 0 ? (
              <p className="placeholder">Aún no tienes transcripciones guardadas.</p>
            ) : (
              <div className="cards-list">
                {savedTranscriptions.map(item => (
                  <div key={item.id} className="transcription-card">
                    <div className="card-header">
                      {editingId === item.id ? (
                        <input
                          className="title-input"
                          value={editingTitle}
                          onChange={(e) => setEditingTitle(e.target.value)}
                          onKeyDown={async (e) => {
                            if (e.key === 'Enter') {
                              try {
                                const res = await axios.post('./rename_transcription.php', { id: item.id, title: editingTitle }, { withCredentials: true });
                                if (res.data && res.data.success) {
                                  setSavedTranscriptions(prev => prev.map(p => p.id === item.id ? res.data.record : p));
                                  setEditingId(null);
                                  setEditingTitle('');
                                }
                              } catch (err) {
                                console.error('Rename error', err);
                                alert('Error renombrando la transcripción');
                              }
                            } else if (e.key === 'Escape') {
                              setEditingId(null);
                              setEditingTitle('');
                            }
                          }}
                          autoFocus
                        />
                      ) : (
                        <span>{item.title && item.title.trim() !== '' ? item.title : new Date(item.created_at).toLocaleString()}</span>
                      )}
                    </div>
                    <div className="card-body">
                      <strong>Transcripción:</strong>
                      <div className="small-text">{item.transcript}</div>
                      <strong>Resumen:</strong>
                      <div className="small-text">{item.summary}</div>
                    </div>
                    <div className="card-actions">
                      {editingId === item.id ? (
                        <>
                          <button className="btn-small" onClick={async () => {
                            try {
                              const res = await axios.post('./rename_transcription.php', { id: item.id, title: editingTitle }, { withCredentials: true });
                              if (res.data && res.data.success) {
                                setSavedTranscriptions(prev => prev.map(p => p.id === item.id ? res.data.record : p));
                                setEditingId(null);
                                setEditingTitle('');
                              }
                            } catch (err) {
                              console.error('Rename error', err);
                              alert('Error renombrando la transcripción');
                            }
                          }}>Guardar</button>
                          <button className="btn-small" onClick={() => { setEditingId(null); setEditingTitle(''); }}>Cancelar</button>
                        </>
                      ) : (
                        <button className="btn-small" onClick={() => { setEditingId(item.id); setEditingTitle(item.title || ''); }}>Editar</button>
                      )}
                      <button className="btn-small danger" onClick={async () => {
                        if (!window.confirm('¿Eliminar esta transcripción? Esta acción no se puede deshacer.')) return;
                        try {
                          const res = await axios.post('./delete_transcription.php', { id: item.id }, { withCredentials: true });
                          if (res.data && res.data.success) {
                            setSavedTranscriptions(prev => prev.filter(p => p.id !== item.id));
                          }
                        } catch (err) {
                          console.error('Delete error', err);
                          alert('Error eliminando la transcripción');
                        }
                      }}>Borrar</button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="right-column card">
            <h3>Transcripción:</h3>
            {transcription ? (
              <div className="text-box" aria-live="polite">{transcription}</div>
            ) : (
              <p className="placeholder">Aquí aparecerá la transcripción.</p>
            )}

            <h3>Resumen:</h3>
            {summary ? (
              <div className="text-box" aria-live="polite">{summary}</div>
            ) : (
              <p className="placeholder">Aquí aparecerá el resumen.</p>
            )}
          </div>
        </div>
      </header>
    </div>
  );
}

export default App;
