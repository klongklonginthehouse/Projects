import { useState } from 'react';
import { View, Text, TextInput, ScrollView } from 'react-native';
import { api } from '../api';
import { C } from '../theme';
import { Btn, Chip, s } from '../ui';

export default function AuthScreen({ onAuthed }) {
  const [mode, setMode] = useState('login');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState('student');
  const [err, setErr] = useState('');
  const [busy, setBusy] = useState(false);

  const go = async () => {
    setErr('');
    setBusy(true);
    try {
      const body = mode === 'login' ? { email, password } : { name, email, password, role };
      const res = await api(mode === 'login' ? '/login' : '/register', { method: 'POST', body });
      await onAuthed(res.token, res.user);
    } catch (e) {
      setErr(e.message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScrollView style={{ backgroundColor: C.paper }} contentContainerStyle={{ padding: 24, paddingTop: 80 }} keyboardShouldPersistTaps="handled">
      <Text style={{ fontSize: 32, fontWeight: '700', color: C.maroon }}>IskolarNotes</Text>
      <Text style={{ color: C.muted, marginTop: 4, marginBottom: 24 }}>Notes and past exams, shared by students, checked by students.</Text>

      {mode === 'register' && (
        <>
          <Text style={s.label}>Full name</Text>
          <TextInput style={s.input} value={name} onChangeText={setName} />
        </>
      )}
      <Text style={s.label}>Email</Text>
      <TextInput style={s.input} value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />
      <Text style={s.label}>Password</Text>
      <TextInput style={s.input} value={password} onChangeText={setPassword} secureTextEntry />

      {mode === 'register' && (
        <>
          <Text style={s.label}>I want to</Text>
          <View style={{ flexDirection: 'row' }}>
            <Chip label="Study from notes" active={role === 'student'} onPress={() => setRole('student')} />
            <Chip label="Upload and study" active={role === 'author'} onPress={() => setRole('author')} />
          </View>
        </>
      )}

      {!!err && <Text style={{ color: C.maroon, marginTop: 12 }}>{err}</Text>}
      <View style={{ marginTop: 20 }}>
        <Btn title={mode === 'login' ? 'Log in' : 'Create account'} onPress={go} disabled={busy} color={C.maroon} />
      </View>
      <View style={{ marginTop: 12 }}>
        <Btn outline title={mode === 'login' ? 'I need an account' : 'I already have an account'} onPress={() => { setErr(''); setMode(mode === 'login' ? 'register' : 'login'); }} />
      </View>
    </ScrollView>
  );
}
