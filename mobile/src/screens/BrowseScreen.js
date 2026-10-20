import { useCallback, useEffect, useState } from 'react';
import { View, Text, TextInput, FlatList, ScrollView, TouchableOpacity, RefreshControl } from 'react-native';
import { api } from '../api';
import { C, TYPE_LABEL } from '../theme';
import { Chip, Empty, Tag, s } from '../ui';

export default function BrowseScreen({ navigation }) {
  const [q, setQ] = useState('');
  const [depts, setDepts] = useState([]);
  const [dept, setDept] = useState(null);
  const [type, setType] = useState(null);
  const [sort, setSort] = useState('top');
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);
  const [loading, setLoading] = useState(false);
  const [err, setErr] = useState('');

  useEffect(() => {
    api('/departments').then(setDepts).catch(() => {});
  }, []);

  const load = useCallback(async (p = 1) => {
    setLoading(true);
    setErr('');
    try {
      const params = new URLSearchParams({ page: p, sort });
      if (q.trim()) params.set('q', q.trim());
      if (dept) params.set('department_id', dept);
      if (type) params.set('type', type);
      const res = await api(`/resources?${params}`);
      setItems((prev) => (p === 1 ? res.data : [...prev, ...res.data]));
      setPage(res.current_page);
      setLast(res.last_page);
    } catch (e) {
      setErr(e.message);
    } finally {
      setLoading(false);
    }
  }, [q, dept, type, sort]);

  // Search as you type, after a short pause.
  useEffect(() => {
    const t = setTimeout(() => load(1), 300);
    return () => clearTimeout(t);
  }, [load]);

  // Refresh when coming back from the detail screen (votes, unlocks).
  useEffect(() => navigation.addListener('focus', () => load(1)), [navigation, load]);

  return (
    <View style={{ flex: 1, backgroundColor: C.paper }}>
      <View style={{ padding: 12 }}>
        <TextInput style={s.input} placeholder="Search by title, course code or instructor" value={q} onChangeText={setQ} />
      </View>
      <View style={{ height: 44 }}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 12 }}>
          <Chip label="All departments" active={!dept} onPress={() => setDept(null)} />
          {depts.map((d) => <Chip key={d.id} label={d.name} active={dept === d.id} onPress={() => setDept(d.id)} />)}
        </ScrollView>
      </View>
      <View style={{ height: 44 }}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 12 }}>
          <Chip label="All types" active={!type} onPress={() => setType(null)} />
          {Object.entries(TYPE_LABEL).map(([k, v]) => <Chip key={k} label={v} active={type === k} onPress={() => setType(k)} />)}
          <Chip label={sort === 'top' ? 'Most upvoted' : 'Newest'} onPress={() => setSort(sort === 'top' ? 'new' : 'top')} />
        </ScrollView>
      </View>

      {!!err && <Text style={{ color: C.maroon, padding: 12 }}>{err}</Text>}

      <FlatList
        data={items}
        keyExtractor={(r) => String(r.id)}
        refreshControl={<RefreshControl refreshing={loading && page === 1} onRefresh={() => load(1)} />}
        onEndReached={() => !loading && page < last && load(page + 1)}
        ListEmptyComponent={!loading && <Empty text="Nothing matches those filters. Clear the search or pick another department." />}
        contentContainerStyle={{ padding: 12 }}
        renderItem={({ item: r }) => (
          <TouchableOpacity onPress={() => navigation.navigate('Detail', { id: r.id })} style={{ flexDirection: 'row', backgroundColor: C.card, borderWidth: 1, borderColor: C.line, borderRadius: 4, marginBottom: 10, overflow: 'hidden' }}>
            <View style={{ width: 78, backgroundColor: C.green, alignItems: 'center', justifyContent: 'center', padding: 6 }}>
              <Text style={{ color: '#fff', fontSize: 17, fontWeight: '700' }}>{r.course_code}</Text>
              <Text style={{ color: '#fff', opacity: 0.8, fontSize: 11, textAlign: 'center' }}>{TYPE_LABEL[r.type]}</Text>
            </View>
            <View style={{ flex: 1, padding: 12 }}>
              <Text style={{ fontWeight: '700', color: C.ink, fontSize: 15 }}>{r.title}</Text>
              <Text style={{ color: C.muted, marginTop: 2, fontSize: 13 }}>{r.instructor} · {r.department?.name}</Text>
              <View style={{ flexDirection: 'row', gap: 8, marginTop: 8, alignItems: 'center' }}>
                {r.is_official && <Tag text="Official" />}
                <Text style={{ color: r.voted ? C.maroon : C.muted, fontSize: 13 }}>{r.votes_count} upvotes</Text>
                <Text style={{ color: C.muted, fontSize: 13 }}>{r.owned ? 'Unlocked' : r.cost ? `${r.cost} credits` : 'Free'}</Text>
              </View>
            </View>
          </TouchableOpacity>
        )}
      />
    </View>
  );
}
