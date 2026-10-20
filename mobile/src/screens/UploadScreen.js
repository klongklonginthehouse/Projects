import { useEffect, useState } from 'react';
import { View, Text, TextInput, ScrollView } from 'react-native';
import * as DocumentPicker from 'expo-document-picker';
import { api } from '../api';
import { C, TYPE_LABEL } from '../theme';
import { Btn, Chip, s } from '../ui';

export default function UploadScreen({ user }) {
  const faculty = user.role === 'faculty';
  const types = Object.keys(TYPE_LABEL).filter((t) => faculty || t !== 'syllabus');

  const [depts, setDepts] = useState([]);
  const [f, setF] = useState({ title: '', course_code: '', instructor: '', description: '', department_id: null, type: faculty ? 'syllabus' : 'lecture_notes' });
  const [file, setFile] = useState(null);
  const [msg, setMsg] = useState('');
  const [ok, setOk] = useState(false);
  const [busy, setBusy] = useState(false);

  useEffect(() => { api('/departments').then((d) => { setDepts(d); setF((x) => ({ ...x, department_id: d[0]?.id })); }); }, []);
  const set = (k) => (v) => setF((x) => ({ ...x, [k]: v }));

  const pick = async () => {
    const res = await DocumentPicker.getDocumentAsync({ copyToCacheDirectory: true });
    if (!res.canceled) setFile(res.assets[0]);
  };

  const submit = async () => {
    setMsg('');
    setOk(false);
    if (!file) { setMsg('Choose a file to upload.'); return; }
    setBusy(true);
    try {
      const form = new FormData();
      Object.entries(f).forEach(([k, v]) => v != null && form.append(k, String(v)));
      form.append('file', { uri: file.uri, name: file.name, type: file.mimeType || 'application/octet-stream' });
      await api('/resources', { method: 'POST', form });
      setOk(true);
      setMsg(faculty ? 'Published as official material.' : 'Uploaded. Moderators will review it. You earn 5 credits once it is verified.');
      setF({ ...f, title: '', course_code: '', instructor: '', description: '' });
      setFile(null);
    } catch (e) {
      setMsg(e.message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScrollView style={{ backgroundColor: C.paper }} contentContainerStyle={{ padding: 16 }} keyboardShouldPersistTaps="handled">
      <Text style={s.h1}>{faculty ? 'Publish official material' : 'Upload a resource'}</Text>
      <Text style={s.label}>Title</Text>
      <TextInput style={s.input} value={f.title} onChangeText={set('title')} />
      <Text style={s.label}>Course code</Text>
      <TextInput style={s.input} value={f.course_code} onChangeText={set('course_code')} autoCapitalize="characters" placeholder="CS 12" />
      <Text style={s.label}>Instructor</Text>
      <TextInput style={s.input} value={f.instructor} onChangeText={set('instructor')} />
      <Text style={s.label}>Department</Text>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6 }}>
        {depts.map((d) => <Chip key={d.id} label={d.name} active={f.department_id === d.id} onPress={() => set('department_id')(d.id)} />)}
      </View>
      <Text style={s.label}>Type</Text>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6 }}>
        {types.map((t) => <Chip key={t} label={TYPE_LABEL[t]} active={f.type === t} onPress={() => set('type')(t)} />)}
      </View>
      <Text style={s.label}>Description</Text>
      <TextInput style={[s.input, { height: 80, textAlignVertical: 'top' }]} multiline value={f.description} onChangeText={set('description')} />

      <View style={{ marginTop: 16 }}>
        <Btn outline title={file ? `Selected: ${file.name}` : 'Choose file (PDF, Word, slides, image)'} onPress={pick} />
      </View>
      {!!msg && <Text style={{ color: ok ? C.green : C.maroon, marginTop: 12 }}>{msg}</Text>}
      <View style={{ marginTop: 16 }}>
        <Btn title={faculty ? 'Publish' : 'Upload'} onPress={submit} disabled={busy} color={C.maroon} />
      </View>
    </ScrollView>
  );
}
