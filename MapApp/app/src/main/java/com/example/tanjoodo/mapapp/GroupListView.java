package com.example.tanjoodo.mapapp;

import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.support.v7.app.ActionBar;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.view.View;
import android.widget.EditText;
import android.widget.ListView;
import android.widget.TextView;

public class GroupListView extends AppCompatActivity {

    final private Context c = this;
    String [] list = {"Family", "Friends"};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_group_list_view);
        ActionBar ab = getSupportActionBar();
        if (ab != null) {
            ab.setTitle("My Groups");
        }
        ListView lv = (ListView) findViewById(R.id.group_list);
        View.OnClickListener tvListener = new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                Intent result = new Intent();
                if (((TextView)view).getText().toString().equals("Family")) {
                    result.putExtra(MyAdapter.EXTRA_GID, "1");
                } else {
                    result.putExtra(MyAdapter.EXTRA_GID, "0");
                }

                setResult(RESULT_OK, result);
                finish();
            }
        };

        View.OnClickListener ibListener = new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                AlertDialog.Builder adb = new AlertDialog.Builder(c);
                View parent = (View) view.getParent();
                final String group = ((TextView) parent.findViewById(R.id.list_item_content)).getText().toString();
                adb.setTitle("Group '" + group + "'");
                adb.setItems(new CharSequence[]{"View members", "Change title", "Leave group"}, new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialogInterface, int i) {
                        switch (i) {
                            case 0:
                                Intent go_to_group_view = new Intent(c, GroupView.class);
                                if (group.equals("Family")) {
                                    go_to_group_view.putExtra(MyAdapter.EXTRA_GID, "1");
                                } else {
                                    go_to_group_view.putExtra(MyAdapter.EXTRA_GID, "0");
                                }

                                startActivity(go_to_group_view);
                                break;
                            case 1:
                                AlertDialog.Builder changeTitleDialog = new AlertDialog.Builder(c);
                                EditText editText = new EditText(c);
                                editText.setHint("New group name...");
                                changeTitleDialog.setView(editText);
                                changeTitleDialog.setPositiveButton("OK", new DialogInterface.OnClickListener() {
                                    @Override
                                    public void onClick(DialogInterface dialogInterface, int i) {
                                        dialogInterface.cancel();
                                    }
                                });
                                changeTitleDialog.show();
                                break;
                            case 2:
                                AlertDialog.Builder confirmLeaveGroup = new AlertDialog.Builder(c);
                                confirmLeaveGroup.setPositiveButton("Yes", new DialogInterface.OnClickListener() {
                                    @Override
                                    public void onClick(DialogInterface dialogInterface, int i) {
                                        dialogInterface.cancel();
                                    }
                                });

                                confirmLeaveGroup.setNegativeButton("No", new DialogInterface.OnClickListener() {
                                    @Override
                                    public void onClick(DialogInterface dialogInterface, int i) {
                                        dialogInterface.cancel();
                                    }
                                });

                                confirmLeaveGroup.setMessage("Are you sure you want to leave this group?");
                                confirmLeaveGroup.show();
                                break;
                        }
                    }
                });
                adb.show();
            }
        };

        lv.setAdapter(new MyAdapter(this, R.layout.list_item, list, tvListener, ibListener));
    }
}
