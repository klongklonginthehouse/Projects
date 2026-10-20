import { useCallback, useEffect, useState } from 'react';
import { View, Text, FlatList } from 'react-native';
import { api } from '../api';
import { C, TYPE_LABEL } from '../theme';
import { Btn, Empty, Tag } from '../ui';

const ROLE = { student: 'Undergraduate student', author: 'Student author / tutor', moderator: 'Student moderator', faculty: 'Department admin / faculty' };

export default function ProfileScreen({ navigation, user, onLogout, refreshUser }) {
  const [shelf, setShelf] = useState([]);

  const load = useCallback(() => {
    refreshUser();
    api('/me/shelf').then(setShelf).catch(() => {});
  }, [refreshUser]);

  useEffect(() => navigation.addListener('focus', load), [navigation, load]);

  return (
    <FlatList
      style={{ backgroundColor: C.paper }}
      contentContainerStyle={{ padding: 16 }}
      data={shelf}
      keyExtractor={(r) => String(r.id)}
      ListHeaderComponent={
        <View style={{ marginBottom: 12 }}>
          <View style={{ backgroundColor: C.card, borderWidth: 1, borderColor: C.line, borderRadius: 4, padding: 16 }}>
            <Text style={{ fontSize: 20, fontWeight: '700', color: C.ink }}>{user.name}</Text>
            <Text style={{ color: C.muted }}>{ROLE[user.role]}</Text>
            <Text style={{ marginTop: 10, color: C.maroon, fontSize: 17, fontWeight: '600' }}>{user.badge || 'No badge yet'}</Text>
            <Text style={{ color: C.muted, marginTop: 2 }}>{user.credits} credits · {user.reputation} reputation points</Text>
          </View>
          <Text style={{ fontSize: 18, fontWeight: '700', color: C.ink, marginTop: 20, marginBottom: 6 }}>Your shelf</Text>
        </View>
      }
      ListEmptyComponent={<Empty text="Your shelf is empty. Download something from Browse to keep it here." />}
      ListFooterComponent={<View style={{ marginTop: 16 }}><Btn outline title="Log out" onPress={onLogout} /></View>}
      renderItem={({ item: r }) => (
        <View style={{ backgroundColor: C.card, borderWidth: 1, borderColor: C.line, borderRadius: 4, padding: 12, marginBottom: 8 }}>
          <Text style={{ fontWeight: '600', color: C.ink }}>{r.title}</Text>
          <Text style={{ color: C.muted, fontSize: 13, marginTop: 2 }}>{r.course_code} · {TYPE_LABEL[r.type]}</Text>
          <View style={{ marginTop: 6, alignSelf: 'flex-start' }}>
            <Tag
              text={r.status === 'verified' ? 'Verified' : r.status === 'pending' ? 'Awaiting review' : 'Rejected'}
              color={r.status === 'verified' ? C.green : r.status === 'pending' ? C.amber : C.maroon}
            />
          </View>
        </View>
      )}
    />
  );
}
