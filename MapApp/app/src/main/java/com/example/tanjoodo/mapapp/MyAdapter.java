package com.example.tanjoodo.mapapp;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.ImageButton;
import android.widget.TextView;

public class MyAdapter extends ArrayAdapter<String> {
    public static final String EXTRA_GID = "com.example.tanjoodo.mapapp.GID";
    View.OnClickListener tvListener, ibListener;
    public MyAdapter(Context context, int resource, String[] objects,
                     View.OnClickListener tvListener, View.OnClickListener ibListener) {
        super(context,resource, objects);
        this.tvListener = tvListener;
        this.ibListener = ibListener;
    }

    @Override
    public View getView(int position, View convertView, ViewGroup parent) {
        View view = convertView;
        if (view == null) {
            LayoutInflater inflater = (LayoutInflater) getContext().getSystemService(Context.LAYOUT_INFLATER_SERVICE);
            view = inflater.inflate(R.layout.list_item, null);
        }

        TextView tv = (TextView)  view.findViewById(R.id.list_item_content);
        tv.setText(this.getItem(position));
        tv.setOnClickListener(tvListener);
        ImageButton optionButton = (ImageButton) view.findViewById(R.id.list_item_options);
        optionButton.setOnClickListener(ibListener);

        return view;
    }
}