import { useCallback, useEffect, useState } from 'react';
import { View, Text, FlatList, RefreshControl } from 'react-native';
import { api } from '../api';
import { C, TYPE_LABEL } from '../theme';
import { Btn, Empty, Tag } from '../ui';

export default function ReviewScreen({ navigation }) {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [msg, setMsg] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try { setItems(await api('/review/queue')); } catch (e) { setMsg(e.message); } finally { setLoading(false); }
  }, []);

  useEffect(() => navigation.addListener('focus', load), [navigation, load]);

  const act = async (id, action, done) => {
    setMsg('');
    try { await api(`/review/${id}/${action}`, { method: 'POST' }); setMsg(done); load(); } catch (e) { setMsg(e.message); }
  };

  return (
    <View style={{ flex: 1, backgroundColor: C.paper }}>
      {!!msg && <Text style={{ padding: 12, color: C.maroon }}>{msg}</Text>}
      <FlatList
        data={items}
        keyExtractor={(r) => String(r.id)}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />}
        ListEmptyComponent={!loading && <Empty text="The queue is clear. New uploads and reports will show up here." />}
        contentContainerStyle={{ padding: 12 }}
        renderItem={({ item: r }) => (
          <View style={{ backgroundColor: C.card, borderWidth: 1, borderColor: C.line, borderRadius: 4, padding: 12, marginBottom: 10 }}>
            <Text style={{ fontWeight: '700', color: C.ink }}>{r.title}</Text>
            <Text style={{ color: C.muted, marginTop: 2, fontSize: 13 }}>{r.course_code} · {TYPE_LABEL[r.type]} · by {r.author?.name}</Text>
            <View style={{ flexDirection: 'row', gap: 8, marginTop: 8 }}>
              <Tag text={r.status === 'pending' ? 'Awaiting review' : 'Verified'} color={C.amber} />
              {r.open_reports > 0 && <Tag text={`${r.open_reports} copyright report${r.open_reports > 1 ? 's' : ''}`} color={C.maroon} />}
            </View>
            {r.reports?.[0]?.reason && (
              <Text style={{ color: C.muted, marginTop: 6, fontSize: 13 }}>Report: {r.reports[0].reason}</Text>
            )}
            <View style={{ flexDirection: 'row', gap: 8, marginTop: 12, flexWrap: 'wrap' }}>
              <Btn small title={r.status === 'pending' ? 'Verify' : 'Keep'} onPress={() => act(r.id, 'approve', 'Marked verified.')} />
              <Btn small color={C.maroon} title="Reject" onPress={() => act(r.id, 'reject', 'Rejected and removed from search.')} />
              {r.open_reports > 0 && <Btn small outline title="Dismiss reports" onPress={() => act(r.id, 'dismiss-reports', 'Reports dismissed.')} />}
            </View>
          </View>
        )}
      />
    </View>
  );
}
