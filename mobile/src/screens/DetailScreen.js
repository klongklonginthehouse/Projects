import { useEffect, useState } from 'react';
import { View, Text, ScrollView, Alert } from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { api, API_URL, getToken } from '../api';
import { C, TYPE_LABEL } from '../theme';
import { Btn, Tag } from '../ui';

export default function DetailScreen({ route, onCreditsChanged }) {
  const [r, setR] = useState(null);
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState('');

  const load = () => api(`/resources/${route.params.id}`).then(setR).catch((e) => setMsg(e.message));
  useEffect(() => { load(); }, []);

  if (!r) return <Text style={{ padding: 20 }}>{msg || 'Loading...'}</Text>;

  const vote = async () => {
    try {
      const res = await api(`/resources/${r.id}/vote`, { method: 'POST' });
      setR({ ...r, voted: res.voted, votes_count: res.votes_count });
    } catch (e) { setMsg(e.message); }
  };

  const download = async () => {
    setBusy(true);
    setMsg('');
    try {
      await api(`/resources/${r.id}/unlock`, { method: 'POST' });
      onCreditsChanged?.();
      // Saved in the app's document folder, so unlocked notes stay available offline.
      const dest = FileSystem.documentDirectory + `${r.id}-${r.original_name || 'file'}`;
      const res = await FileSystem.downloadAsync(`${API_URL}/resources/${r.id}/file`, dest, {
        headers: { Authorization: `Bearer ${getToken()}` },
      });
      if (res.status !== 200) throw new Error('The file could not be downloaded.');
      setR({ ...r, owned: true });
      if (await Sharing.isAvailableAsync()) await Sharing.shareAsync(res.uri);
      else setMsg('Saved to your device.');
    } catch (e) {
      setMsg(e.message);
    } finally {
      setBusy(false);
    }
  };

  const report = () =>
    Alert.alert('Report copyright issue', 'Send this resource to the moderators for review?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Send report', onPress: async () => { try { const x = await api(`/resources/${r.id}/report`, { method: 'POST' }); setMsg(x.message); } catch (e) { setMsg(e.message); } } },
    ]);

  const label = r.owned || r.cost === 0 ? 'Download' : `Download for ${r.cost} credits`;

  return (
    <ScrollView style={{ backgroundColor: C.paper }} contentContainerStyle={{ padding: 16 }}>
      <Text style={{ fontSize: 22, fontWeight: '700', color: C.ink }}>{r.title}</Text>
      <Text style={{ color: C.muted, marginTop: 6 }}>{r.course_code} · {r.instructor} · {r.department?.name}</Text>
      <View style={{ flexDirection: 'row', gap: 8, marginTop: 10 }}>
        <Tag text={TYPE_LABEL[r.type]} color={C.muted} />
        {r.is_official && <Tag text="Official" />}
      </View>
      <Text style={{ marginTop: 16, color: C.ink, lineHeight: 21 }}>{r.description || 'No description provided.'}</Text>
      <Text style={{ marginTop: 12, color: C.muted }}>Uploaded by {r.author?.name} · {r.votes_count} upvotes · {r.downloads_count} downloads</Text>

      {!!msg && <Text style={{ marginTop: 12, color: C.maroon }}>{msg}</Text>}

      <View style={{ marginTop: 20, gap: 10 }}>
        <Btn title={label} onPress={download} disabled={busy} />
        <Btn outline title={r.voted ? 'Remove upvote' : 'Upvote'} onPress={vote} />
        <Btn outline title="Report copyright issue" onPress={report} />
      </View>
    </ScrollView>
  );
}
