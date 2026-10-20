import { Text, TouchableOpacity, View, StyleSheet } from 'react-native';
import { C } from './theme';

export function Btn({ title, onPress, color = C.green, outline, disabled, small }) {
  return (
    <TouchableOpacity
      onPress={onPress}
      disabled={disabled}
      accessibilityRole="button"
      style={[
        s.btn,
        small && { paddingVertical: 6, paddingHorizontal: 10 },
        outline ? { borderWidth: 1, borderColor: C.line, backgroundColor: C.card } : { backgroundColor: color },
        disabled && { opacity: 0.5 },
      ]}
    >
      <Text style={{ color: outline ? C.ink : '#fff', fontWeight: '600', fontSize: small ? 13 : 15 }}>{title}</Text>
    </TouchableOpacity>
  );
}

export function Chip({ label, active, onPress }) {
  return (
    <TouchableOpacity
      onPress={onPress}
      style={[s.chip, active && { backgroundColor: C.maroon, borderColor: C.maroon }]}
      accessibilityState={{ selected: !!active }}
    >
      <Text style={{ color: active ? '#fff' : C.ink, fontSize: 13 }}>{label}</Text>
    </TouchableOpacity>
  );
}

export function Empty({ text }) {
  return (
    <View style={s.empty}>
      <Text style={{ color: C.muted, textAlign: 'center' }}>{text}</Text>
    </View>
  );
}

export function Tag({ text, color = C.green }) {
  return (
    <View style={{ borderWidth: 1, borderColor: color, borderRadius: 3, paddingHorizontal: 6, paddingVertical: 2 }}>
      <Text style={{ color, fontSize: 12 }}>{text}</Text>
    </View>
  );
}

export const s = StyleSheet.create({
  btn: { paddingVertical: 11, paddingHorizontal: 16, borderRadius: 4, alignItems: 'center' },
  chip: { borderWidth: 1, borderColor: C.line, backgroundColor: C.card, borderRadius: 3, paddingHorizontal: 10, paddingVertical: 6, marginRight: 8 },
  empty: { margin: 20, padding: 24, borderWidth: 1, borderStyle: 'dashed', borderColor: C.line, borderRadius: 4 },
  input: { borderWidth: 1, borderColor: C.line, backgroundColor: C.card, borderRadius: 4, paddingHorizontal: 12, paddingVertical: 10, color: C.ink, fontSize: 15 },
  label: { color: C.muted, marginBottom: 4, marginTop: 12, fontSize: 13 },
  h1: { fontFamily: undefined, fontSize: 22, fontWeight: '700', color: C.ink },
});
