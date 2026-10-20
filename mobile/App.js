import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, View } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { StatusBar } from 'expo-status-bar';
import { api, loadToken, saveToken } from './src/api';
import { C } from './src/theme';
import AuthScreen from './src/screens/AuthScreen';
import BrowseScreen from './src/screens/BrowseScreen';
import DetailScreen from './src/screens/DetailScreen';
import UploadScreen from './src/screens/UploadScreen';
import ReviewScreen from './src/screens/ReviewScreen';
import ProfileScreen from './src/screens/ProfileScreen';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

const header = { headerStyle: { backgroundColor: C.maroon }, headerTintColor: '#fff' };

export default function App() {
  const [user, setUser] = useState(null);
  const [ready, setReady] = useState(false);

  const refreshUser = useCallback(() => api('/me').then(setUser).catch(() => {}), []);

  useEffect(() => {
    (async () => {
      try {
        if (await loadToken()) setUser(await api('/me'));
      } catch (e) {
        await saveToken(null); // expired or revoked token
      }
      setReady(true);
    })();
  }, []);

  const onAuthed = async (token, u) => { await saveToken(token); setUser(u); };
  const logout = async () => { try { await api('/logout', { method: 'POST' }); } catch (e) {} await saveToken(null); setUser(null); };

  if (!ready) return <View style={{ flex: 1, justifyContent: 'center' }}><ActivityIndicator color={C.maroon} /></View>;
  if (!user) return <AuthScreen onAuthed={onAuthed} />;

  const staff = user.role === 'moderator' || user.role === 'faculty';
  const canUpload = user.role !== 'student';

  const Tabs = () => (
    <Tab.Navigator screenOptions={{ ...header, tabBarActiveTintColor: C.maroon }}>
      <Tab.Screen name="Browse" component={BrowseScreen} options={{ title: 'IskolarNotes' }} />
      {canUpload && <Tab.Screen name="Upload" options={{ title: user.role === 'faculty' ? 'Publish' : 'Upload' }}>{() => <UploadScreen user={user} />}</Tab.Screen>}
      {staff && <Tab.Screen name="Review" component={ReviewScreen} options={{ title: 'Review queue' }} />}
      <Tab.Screen name="Profile" options={{ title: 'My shelf' }}>
        {(p) => <ProfileScreen {...p} user={user} onLogout={logout} refreshUser={refreshUser} />}
      </Tab.Screen>
    </Tab.Navigator>
  );

  return (
    <NavigationContainer>
      <StatusBar style="light" />
      <Stack.Navigator screenOptions={header}>
        <Stack.Screen name="Tabs" component={Tabs} options={{ headerShown: false }} />
        <Stack.Screen name="Detail" options={{ title: 'Resource' }}>
          {(p) => <DetailScreen {...p} onCreditsChanged={refreshUser} />}
        </Stack.Screen>
      </Stack.Navigator>
    </NavigationContainer>
  );
}
